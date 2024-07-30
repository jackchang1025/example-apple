<?php

namespace App\Apple\Proxy\Exception;

class ProxyConfigurationNotFoundException extends \Exception
{
    public function __construct(string $driver)
    {
        parent::__construct("Configuration for driver {$driver} not found.");
    }
}
