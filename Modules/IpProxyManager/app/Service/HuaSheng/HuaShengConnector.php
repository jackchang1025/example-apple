<?php

namespace Modules\IpProxyManager\Service\HuaSheng;

use Modules\IpProxyManager\Service\ProxyConnector;
use Saloon\Traits\Plugins\AcceptsJson;

class HuaShengConnector extends ProxyConnector
{
    use AcceptsJson;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://mobile.huashengdaili.com/';
    }

    public ?int $tries = 5;
}
