<?php

use App\Http\Middleware\BlackListIpsMiddleware;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\ValidationException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\UnauthorizedException;
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
