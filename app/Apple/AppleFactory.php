<?php

namespace App\Apple;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

readonly class AppleFactory
{
    public function __construct(
        protected CacheInterface $cache,
        protected LoggerInterface $logger,
    ) {
    }

    public function create(string $clientId): Apple
    {
       return new Apple(cache: $this->cache, logger: $this->logger,clientId: $clientId);
    }
}
