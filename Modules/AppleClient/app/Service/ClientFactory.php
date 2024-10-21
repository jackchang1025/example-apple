<?php

namespace Modules\AppleClient\Service;

use Modules\AppleClient\Service\Cookies\Cookies;
use Modules\AppleClient\Service\Store\CacheStore;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Request;

class ClientFactory
{

    public function __construct(
        protected AppleClient $client,
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected ProxyService $proxyService
    ) {
    }

    public function getClient(string $sessionId, ?array $config = null): AppleClient
    {

        return $this->client->withCookies(
            new Cookies(
                cache: $this->cache,
                key: $sessionId,
                ttl: 3600,
            )
        )->withHeaderRepositories(
            new CacheStore(
                cache: $this->cache,
                key: $sessionId,
                ttl: 3600,
                prx: 'header',
                defaultData: []
            )
        )
            ->withHandleRetry(function (RequestException $exception, Request $request) {

                if ($this->isConnectionException($exception) && $this->proxyService->isProxyEnabled()) {
                    $this->proxyService->refreshProxy();

                    return true;
                }

                return false;
            })
            ->withLogger($this->logger)
            ->withTries(5)
            ->withProxy($this->proxyService)
            ->withConfig($config ?? config('appleclient'));
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
}
