<?php

namespace App\Apple\Service\Client\Middleware;

use Psr\Http\Message\RequestInterface;

interface RequestMiddlewareInterface
{
    public function __invoke(RequestInterface $request): RequestInterface;
}
