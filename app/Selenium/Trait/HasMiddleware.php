<?php

namespace App\Selenium\Trait;


trait HasMiddleware
{
    protected array $middlewares = [];

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function addMiddleware(mixed $middleware):static
    {
        array_push($this->middlewares, ...(is_array($middleware) ? $middleware : func_get_args()));

        return $this;
    }
}
