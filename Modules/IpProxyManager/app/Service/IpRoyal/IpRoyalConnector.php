<?php

namespace Modules\IpProxyManager\Service\IpRoyal;

use Modules\IpProxyManager\Service\ProxyConnector;
use Saloon\Traits\Plugins\AcceptsJson;

class IpRoyalConnector extends ProxyConnector
{
    use AcceptsJson;

    /**
     * Default number of retries for failed requests
     */
    public ?int $tries = 3;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.iproyal.com/';
    }

    /**
     * Default headers for all requests
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
