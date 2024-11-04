<?php

namespace Modules\PhoneCode\Service;

use Modules\AppleClient\Service\Trait\HasLogger;
use Saloon\Http\Connector;

class PhoneConnector extends Connector
{
    use HasLogger;

    public function resolveBaseUrl(): string
    {
        return '';
    }

    public function resolveResponseClass(): string
    {
        return Response::class;
    }
}
