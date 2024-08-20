<?php

namespace App\Apple\Service\Client\Middleware;

use Closure;

class MiddlewareManager
{
    protected array $requestMiddlewares = [];
    protected array $responseMiddlewares = [];
    protected array $globalMiddlewares = [
        HeaderMiddleware::class,
        LogsMiddleware::class,
    ];

    public function addRequestMiddleware(RequestMiddlewareInterface|Closure $middleware): self
    {
        $this->requestMiddlewares[] = $middleware;
        return $this;
    }

    public function addResponseMiddleware(ResponseMiddlewareInterface|Closure $middleware): self
    {
        $this->responseMiddlewares[] = $middleware;
        return $this;
    }

    public function addGlobalMiddleware(GlobalMiddlewareInterface $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    public function getRequestMiddlewares(): array
    {
        return array_merge(
            $this->requestMiddlewares,
            $this->globalMiddlewares
        );
    }

    public function getResponseMiddlewares(): array
    {
        return array_merge(
            $this->responseMiddlewares,
            $this->globalMiddlewares
        );
    }
}
