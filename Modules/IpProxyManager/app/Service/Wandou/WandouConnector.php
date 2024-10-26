<?php

namespace Modules\IpProxyManager\Service\Wandou;

use Modules\IpProxyManager\Service\ProxyConnector;
use Saloon\Traits\Plugins\AcceptsJson;

class WandouConnector extends ProxyConnector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return 'https://api.wandouapp.com/';
    }
}
