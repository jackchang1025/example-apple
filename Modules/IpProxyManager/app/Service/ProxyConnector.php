<?php

namespace Modules\IpProxyManager\Service;

use Saloon\Http\Connector;
use Modules\IpProxyManager\Service\Trait\HasLogger;

abstract class ProxyConnector extends Connector
{
    use HasLogger;
}
