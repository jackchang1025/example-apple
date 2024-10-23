<?php

namespace Modules\IpAddress\Service;

use Modules\AppleClient\Service\Trait\HasLogger;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class IpConnector extends Connector
{
    use HasLogger;
    use AcceptsJson;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return '';
    }
}
