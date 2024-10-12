<?php

namespace App\Apple\Trait;

use Closure;

trait HasRetry
{

    protected array|int $tries = 4;

    protected Closure|int $retryDelay = 100;

    protected ?Closure $retryWhenCallback = null;

    protected bool $retryThrow = false;

    public function retry(int $times, Closure|int $delay = 100, ?Closure $when = null): self
    {
        $this->tries             = $times;
        $this->retryDelay        = $delay;
        $this->retryWhenCallback = $when;

        return $this;
    }

    /**
     * @param callable $callback
     * @return callable
     * @throws \Throwable
     */
    public function handleRetry(callable $callback): mixed
    {
        return retry($this->tries, $callback, $this->retryDelay   , $this->retryWhenCallback);
    }

}
