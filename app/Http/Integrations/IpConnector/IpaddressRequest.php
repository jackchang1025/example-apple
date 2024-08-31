<?php

namespace App\Http\Integrations\IpConnector;

use App\Http\Integrations\IpConnector\Responses\IpResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

abstract class IpaddressRequest extends Request
{
    abstract public function extractJsonFromString(Response $response):IpResponse;
}
