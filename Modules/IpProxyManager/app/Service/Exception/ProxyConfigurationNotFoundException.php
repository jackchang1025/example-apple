<?php

namespace Modules\IpProxyManager\Service\Exception;

class ProxyConfigurationNotFoundException extends ProxyException
{
    public function __construct(string $driver)
    {
        parent::__construct("Configuration for driver {$driver} not found.");
    }
}
