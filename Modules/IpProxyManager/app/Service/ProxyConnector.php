<?php

namespace Modules\IpProxyManager\Service;

use Modules\AppleClient\Service\Trait\HasLogger;
use Saloon\Http\Connector;

abstract class ProxyConnector extends Connector
{
    use HasLogger;
}
