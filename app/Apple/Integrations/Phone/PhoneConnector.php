<?php

namespace App\Apple\Integrations\Phone;

use Saloon\Http\Connector;

class PhoneConnector extends Connector
{

    public function resolveBaseUrl(): string
    {
        return '';
    }
}
