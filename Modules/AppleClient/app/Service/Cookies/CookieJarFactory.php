<?php

namespace Modules\AppleClient\Service\Cookies;

use Psr\SimpleCache\CacheInterface;

readonly class CookieJarFactory
{
    public function __construct(
        private CacheInterface $cache,
        private int $ttl = 3600
    ) {
    }

    public function create(string $domain, string $sessionId): CookieJarInterface
    {
        return new Cookies(
            cache: $this->cache,
            key: $this->generateKey($domain, $sessionId),
            ttl: $this->ttl
        );
    }

    protected function generateKey(string $domain, string $sessionId): string
    {
        return sprintf('cookie:%s:%s', $domain, $sessionId);
    }
}
