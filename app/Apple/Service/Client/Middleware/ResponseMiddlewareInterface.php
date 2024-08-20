<?php

namespace App\Apple\Service\Client\Middleware;

use Psr\Http\Message\ResponseInterface;

interface ResponseMiddlewareInterface
{
    public function __invoke(ResponseInterface $response): ResponseInterface;
}
