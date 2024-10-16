<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

readonly class AppleClientFactory
{
    public function __construct(
        protected CacheInterface $cache,
        protected LoggerInterface $logger,
        protected ProxyService $proxyService
    ) {
    }

    /**
     * @param string $sessionId
     * @param array<string, mixed> $config
     *
     * @return AppleClient
     */
    public function create(string $sessionId, ?array $config = null): AppleClient
    {
        return AppleClient::builder($sessionId)
            ->withCache($this->cache)
            ->withLogger($this->logger)
            ->withProxy($this->proxyService)
            ->withConfig($config ?? config('appleclient'))
            ->getClient();
    }
}
