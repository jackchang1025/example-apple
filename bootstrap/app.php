<?php

use App\Http\Middleware\BlackListIpsMiddleware;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\ValidationException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeException;
use Saloon\Exceptions\SaloonException;
use App\Exceptions\AccountAlreadyBindException;
use Weijiajia\SaloonphpAppleClient\Exception\SignInException;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'index/*',
        ]);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'livewire/*',
        ]);
    })
    ->withEvents(
        [
            __DIR__.'/../app/Listeners',
        ]
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(BlackListIpsMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (StolenDeviceProtectionException $e) {
            return response()->json([
                'code'    => 403,
                'message' => $e->getMessage(),
            ]);
        });

        $exceptions->render(function (UnauthorizedException|NotFoundHttpException $e) {

            // 检查请求的是否为资源文件
            $path           = request()->path();
            $isAssetRequest = preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$/i', $path);

            // 如果是资源文件的 404，直接返回 404 响应
            if ($isAssetRequest && $e instanceof NotFoundHttpException) {
                return response()->json([
                    'code'    => 404,
                    'message' => 'Resource not found',
                ], 404);
            }

            // 其他情况保持原有的重定向逻辑
            if (request()->isJson()) {
                return response()->json([
                    'code'    => 302,
                    'message' => $e->getMessage(),
                    'uri' => '/index/signin'
                ]);
            }
            return redirect('/index/signin');
        });

        $exceptions->render(function (VerificationCodeException $e) {
            return response()->json([
                'code'    => 400,
                'message' => '验证码错误',
            ]);
        });

        $exceptions->render(function (ValidationException|ClientException $e) {
            return response()->json([
                'code'    => 400,
                'message' => $e->getMessage(),
            ]);
        });

        $exceptions->render(function (RequestException $e) {
            return response()->json([
                'code'    => 400,
                'message' => $e->response->json(),
            ]);
        });


        $exceptions->render(function (AccountAlreadyBindException $e) {

            return response()->json([
                'code'    => 500,
                'message' => __('apple.signin.account_bind_phone'),
            ]);
        });

        $exceptions->render(function (\Throwable $e) {

            return response()->json([
                'code'    => 500,
                'message' => __('apple.signin.incorrect'),
            ]);
        });

    })->create();
