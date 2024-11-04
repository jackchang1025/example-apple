<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function success(array $data = [], string $message = 'success',int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message, 'data' => $data]);
    }

    protected function error(string $message = 'error', int $code = 500): \Illuminate\Http\JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message]);
    }

    //重定向
    protected function redirect(string $message = 'error', int $code = 302): \Illuminate\Http\JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message]);
    }
}
