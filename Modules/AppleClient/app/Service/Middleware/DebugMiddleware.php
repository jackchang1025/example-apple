<?php

namespace Modules\AppleClient\Service\Middleware;

use Modules\AppleClient\Service\Apple;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

class DebugMiddleware implements MiddlewareInterface
{

    public function __construct(protected Apple $apple)
    {
    }

    public function onRequest(PendingRequest $pendingRequest): PendingRequest
    {
        if ($this->apple->getConfig()->get('debug') === true) {
            $pendingRequest->getConnector()->debug();
        }

        return $pendingRequest;
    }

    public function onResponse(Response $response): Response
    {
        return $response;
    }
}
