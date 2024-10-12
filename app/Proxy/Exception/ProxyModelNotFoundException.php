<?php

namespace App\Proxy\Exception;

class ProxyModelNotFoundException extends ProxyException
{
    public function __construct(string $driver)
    {
        parent::__construct("Configuration for driver {$driver} not found.");
    }
}
