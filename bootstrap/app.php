<?php

use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
    ->withExceptions(function (Exceptions $exceptions) {


//        $exceptions->report(function (ClientException $e) {
//
//
//            return response()->json([
//                'code' =>'302',
//                'message' => $e->getMessage(),
//            ]);
//
//        })->stop();

        $exceptions->render(function (ClientException $e,Request $request) {

            if(in_array($e->getResponse()->getStatusCode(),[401,403])){
                return response()->json([
                    'code' =>'302',
                    'message' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'code' =>$e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ]);
        });



    })->create();
