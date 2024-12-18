<?php

namespace Modules\AppleClient\Service\Header;

use Psr\SimpleCache\CacheInterface;
use \Modules\AppleClient\Service\Store\CacheStore;

readonly class HeaderSynchronizeFactory
{
    public function __construct(
        private CacheInterface $cache,
        private int $ttl = 3600
    ) {
    }

    /**
     * @param string $domain
     * @param string $sessionId
     * @return HeaderSynchronizeInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(string $domain, string $sessionId): HeaderSynchronizeInterface
    {
        return new HeaderSynchronize(
            new CacheStore(
                cache: $this->cache,
                key: $this->generateKey($domain, $sessionId),
                ttl: $this->ttl
            )
        );
    }

    protected function generateKey(string $domain, string $sessionId): string
    {
        return sprintf('header:%s:%s', $domain, $sessionId);
    }
}
