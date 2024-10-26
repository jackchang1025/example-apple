<?php

namespace Modules\IpProxyManager\Service\Exception;

class ProxyConfigurationNotFoundException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
