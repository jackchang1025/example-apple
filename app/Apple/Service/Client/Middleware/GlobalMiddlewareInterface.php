<?php

namespace App\Apple\Service\Client\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface GlobalMiddlewareInterface
{
    public function request(RequestInterface $request): RequestInterface;
    public function response(ResponseInterface $response): ResponseInterface;
}
