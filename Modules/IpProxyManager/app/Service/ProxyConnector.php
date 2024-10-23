<?php

namespace Modules\IpProxyManager\Service;

use Modules\AppleClient\Service\Trait\HasLogger;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;

abstract class ProxyConnector extends Connector
{
    use HasLogger;

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        $response = $exception->getResponse();

        $this->formatResponseLog($response);

        return true;
    }
}
