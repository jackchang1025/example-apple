<?php

namespace Modules\IpProxyManager\Service\Stormproxies;

use Modules\IpProxyManager\Service\ProxyConnector;
use Saloon\Traits\Plugins\AcceptsJson;

class StormConnector extends ProxyConnector
{
    use AcceptsJson;

    /**
     * The Base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.stormproxies.cn';
    }
}
