<?php

use App\Http\Middleware\BlackListIpsMiddleware;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\UnauthorizedException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException as SaloonUnauthorizedException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $exceptions->render(function (UnauthorizedException|NotFoundHttpException|SaloonUnauthorizedException $e) {

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

        $exceptions->render(function (ValidationException|VerificationCodeException|ClientException $e) {
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

        
    })->create();
