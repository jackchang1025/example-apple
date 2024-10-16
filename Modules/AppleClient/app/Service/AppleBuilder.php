<?php

namespace Modules\AppleClient\Service;

use Modules\AppleClient\Service\Cookies\Cookies;
use Modules\AppleClient\Service\Store\CacheStore;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Contracts\ArrayStore as ArrayStoreContract;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Request;

class AppleBuilder
{
    protected ProxyService $proxyService;

    public function __construct(protected AppleClient $client)
    {

    }

    public function getClient(): AppleClient
    {
        return $this->client;
    }

    public function withConfig(ArrayStoreContract|array|string $config, mixed $value = null): self
    {
        $this->client->withConfig($config, $value);

        return $this;
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $this->client->withLogger($logger);

        return $this;
    }

    public function withCache(CacheInterface $cache): self
    {
        $sessionId = $this->client->getSessionId();

        $this->withCookies(
            new Cookies(
                cache: $cache,
                key: $sessionId,
                ttl: 3600,
            )
        );

        $this->client->withHeaderRepositories(
            new CacheStore(
                cache: $cache,
                key: $sessionId,
                ttl: 3600,
                prx: 'header',
                defaultData: []
            )
        );

        $this->client->withCacheStore(
            new CacheStore(
                cache: $cache,
                key: $sessionId,
                ttl: 3600,
                prx: 'stores',
                defaultData: []
            )
        );

        return $this;
    }

    public function withCookies(Cookies $cookies): self
    {
        $this->client->withCookies($cookies);

        return $this;
    }

    public function withTries(int $tries = 5): AppleBuilder
    {
        $this->client->setTries($tries);

        return $this;
    }

    protected function withRetryCallback(): self
    {
        $this->client->setHandleRetry(function (FatalRequestException|RequestException $exception, Request $request) {
            if ($this->isConnectionException($exception)) {
                $this->client->withProxy(
                    $this->proxyService->refreshProxy()?->url
                );

                return true;
            }

            return false;
        });

        return $this;
    }

    /**
     * Determines if the provided exception indicates a fatal connection issue.
     *
     * Inspects the given exception to ascertain whether it is an instance of
     * `FatalRequestException`, which would suggest a critical failure in the request handling.
     *
     * @param SaloonException $exception The exception to evaluate.
     *
     * @return bool True if the exception is a `FatalRequestException`, false otherwise.
     */
    protected function isConnectionException(SaloonException $exception): bool
    {
        return $exception instanceof FatalRequestException;
    }

    public function withProxy(ProxyService $proxyService): self
    {
        $this->proxyService = $proxyService;

        if ($proxyService->enableProxy()) {
            $this->client->withProxy($proxyService->getProxy()?->url);
        }

        $this->withRetryCallback();

        return $this;
    }
}
