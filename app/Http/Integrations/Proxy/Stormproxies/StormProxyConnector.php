<?php

namespace App\Http\Integrations\Proxy\Stormproxies;

use App\Http\Integrations\Proxy\ProxyConnector;
use App\Http\Integrations\Proxy\Trait\HasGuzzleSenderLogger;
use Psr\Log\LoggerInterface;
use Saloon\Traits\Plugins\AcceptsJson;

class StormProxyConnector extends ProxyConnector
{
    use AcceptsJson;
    use HasGuzzleSenderLogger;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * The Base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.stormproxies.cn';
    }
}
