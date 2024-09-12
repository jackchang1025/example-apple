<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\ProxyResponse;
use Closure;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

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
     * @return callable
     */
    public function handleRetry(): callable
    {
        $retries = 0;

        return $this->retryWhenCallback ?? function (Exception $exception, PendingRequest $request) use (&$retries) {

            $retries++;

            if (!$this->isConnectionException($exception)) {
                return false;
            }

            $this->logger->info("Connection exception after {$retries} attempts", [
                'exception' => get_class($exception),
                'message'   => $exception->getMessage(),
                'hasProxy'  => $this->hasProxy($request),
            ]);

            // 判断是否超过重试次数,如果我们的需求是重试三次然后清除代理在试一次，那么就需要减去 1 次，
            //因为当重试次数超过配置的次数，laravel http Illuminate\Http\Client\PendingRequest  send 方法就直接抛出异常，不在进入到重试逻辑中
            if ($retries >= $this->tries - 1) {

                // 判断是否启用了代理配置并清除代理配置并重试
                return $this->handleFinalRetry($request);
            }

            // 判断是否启用了代理配置并刷新代理配置
            return $this->handleProxyRefresh($request);
        };
    }

    /**
     * @param Exception $exception
     * @return bool
     */
    protected function isConnectionException(Exception $exception): bool
    {
        return $exception instanceof ConnectionException ||
            ($exception instanceof RequestException && !empty(
                $exception->getHandlerContext()
                )) ||
            Str::contains($exception->getMessage(), 'cURL error 56: Proxy CONNECT aborted');
    }

    /**
     * @param PendingRequest $request
     * @return bool
     */
    protected function hasProxy(PendingRequest $request): bool
    {
        return isset($request->getOptions()['proxy']);
    }

    /**
     * @param PendingRequest $request
     * @return bool
     */
    protected function handleFinalRetry(PendingRequest $request): bool
    {
        if ($this->hasProxy($request)) {
            $this->clearProxy($request);
            $this->logger->info("Cleared proxy configuration on final retry");

            return true;
        }

        return false;
    }

    /**
     * @param PendingRequest $request
     * @return PendingRequest
     */
    protected function clearProxy(PendingRequest $request): PendingRequest
    {
        return $request->withOptions([
            RequestOptions::PROXY => null,
        ]);
    }

    /**
     * @param PendingRequest $request
     * @return bool|PendingRequest
     */
    protected function handleProxyRefresh(PendingRequest $request): bool|PendingRequest
    {
        if ($this->isProxyEnabled()) {
            if ($proxy = $this->refreshProxyResponse()?->getUrl()) {
                $this->logger->info("Refreshed proxy to {$proxy}");

                return $request->withOptions([RequestOptions::PROXY => $proxy]);
            }

            return $this->clearProxy($request);
        }

        return false;
    }

    abstract protected function isProxyEnabled(): bool;

    abstract protected function refreshProxyResponse(): ?ProxyResponse;
}
