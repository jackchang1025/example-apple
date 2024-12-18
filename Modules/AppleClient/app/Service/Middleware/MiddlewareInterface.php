<?php

namespace Modules\AppleClient\Service\Middleware;

use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

interface MiddlewareInterface
{
    /**
     * 请求发送前的处理
     */
    public function onRequest(PendingRequest $pendingRequest): PendingRequest;

    /**
     * 响应接收后的处理
     */
    public function onResponse(Response $response): Response;
} 