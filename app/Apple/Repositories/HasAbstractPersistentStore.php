<?php

namespace App\Apple\Repositories;

use Psr\SimpleCache\CacheInterface;

trait HasAbstractPersistentStore
{
    abstract public function getCache(): CacheInterface;

    abstract public function getClientId(): string;
}