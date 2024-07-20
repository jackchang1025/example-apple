<?php

namespace App\Apple\Service\User;

use Psr\SimpleCache\CacheInterface;

readonly class UserFactory
{
    public function __construct(private CacheInterface $cache) {}

    public function create(string $guid): User
    {
        return new User($this->cache, $guid);
    }
}
