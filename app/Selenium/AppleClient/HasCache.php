<?php

namespace App\Selenium\AppleClient;

use Psr\SimpleCache\CacheInterface;

trait HasCache
{
    protected ?CacheInterface $cache = null;

    public function setCache(?CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    abstract public function getCache(): ?CacheInterface;
}
