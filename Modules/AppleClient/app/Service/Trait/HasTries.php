<?php

namespace Modules\AppleClient\Service\Trait;

use Closure;

trait HasTries
{
    public ?int $tries = null;

    /**
     * The interval in milliseconds Saloon should wait between retries.
     *
     * For example 500ms = 0.5 seconds.
     *
     * Set to null to disable the retry interval.
     */
    public ?int $retryInterval = null;

    /**
     * Should Saloon use exponential backoff during retries?
     *
     * When true, Saloon will double the retry interval after each attempt.
     */
    public ?bool $useExponentialBackoff = null;

    /**
     * Should Saloon throw an exception after exhausting the maximum number of retries?
     *
     * When false, Saloon will return the last response attempted.
     *
     * Set to null to always throw after maximum retry attempts.
     */
    public ?bool $throwOnMaxTries = null;

    protected null|bool|Closure $retryWhenCallback = null;

    protected ?Closure $handleRetry = null;

    /**
     * Define whether the request should be retried.
     *
     * You can access the response from the RequestException. You can also modify the
     * request before the next attempt is made.
     */
    /**
     * @param callable $callback
     * @return callable
     * @throws \Throwable
     */
    public function handleRetry(callable $callback): mixed
    {
        return retry($this->getTries(), $callback, $this->getRetryInterval(), $this->getRetryWhenCallback());
    }

    public function getSleepTime(int $attempts, int $retryInterval, ?bool $useExponentialBackoff = null): float|int
    {
        return $useExponentialBackoff
            ? $retryInterval * (2 ** ($attempts - 2)) * 1000
            : $retryInterval * 1000;
    }

    public function getTries(): ?int
    {
        return $this->tries;
    }

    public function withTries(?int $tries): static
    {
        $this->tries = $tries;

        return $this;
    }

    public function getRetryInterval(): ?int
    {
        return $this->retryInterval;
    }

    public function withRetryInterval(?int $retryInterval): static
    {
        $this->retryInterval = $retryInterval;

        return $this;
    }

    public function getUseExponentialBackoff(): ?bool
    {
        return $this->useExponentialBackoff;
    }

    public function withUseExponentialBackoff(?bool $useExponentialBackoff): static
    {
        $this->useExponentialBackoff = $useExponentialBackoff;

        return $this;
    }

    public function getThrowOnMaxTries(): ?bool
    {
        return $this->throwOnMaxTries;
    }

    public function withThrowOnMaxTries(?bool $throwOnMaxTries): static
    {
        $this->throwOnMaxTries = $throwOnMaxTries;

        return $this;
    }

    public function getHandleRetry(): ?Closure
    {
        return $this->handleRetry;
    }

    public function withHandleRetry(?Closure $handleRetry): static
    {
        $this->handleRetry = $handleRetry;

        return $this;
    }

    public function withRetryWhenCallback(null|bool|Closure $retryWhenCallback): static
    {
        $this->retryWhenCallback = $retryWhenCallback;

        return $this;
    }

    public function getRetryWhenCallback(): null|bool|Closure
    {
        return $this->retryWhenCallback;
    }
}
