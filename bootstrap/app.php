<?php

use App\Apple\Service\Exception\UnauthorizedException;
use App\Http\Middleware\BlackListIpsMiddleware;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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

        $exceptions->render(function (UnauthorizedException $e) {
            return redirect('/');
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return redirect('/');
        });

        $exceptions->render(function (ClientException $e, Request $request) {
            $statusCode = $e->getResponse()->getStatusCode();
            if (in_array($statusCode, [401, 403])) {
                return response()->json([
                    'code'    => '302',
                    'message' => $e->getMessage(),
                ], 302);
            }

            return response()->json([
                'code'    => $statusCode,
                'message' => $e->getMessage(),
            ], $statusCode);
        });


    })->create();
