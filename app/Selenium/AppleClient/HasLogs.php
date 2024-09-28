<?php

namespace App\Selenium\AppleClient;

use App\Selenium\PendingRequest;
use Psr\Log\LoggerInterface;

trait HasLogs
{
    protected ?LoggerInterface $logger = null;

    public function bootHasLogs(PendingRequest $pendingRequest): void
    {
        $pendingRequest->getConnector()->addMiddleware(function (PendingRequest $pendingRequest,\Closure $next) {

            $connector = $pendingRequest->getConnector();
            $request = $pendingRequest->getRequest();

            Log::debug('request', [
                'method' => $request->getMethod(),
                'uri' => $pendingRequest->getUrl(),
                'config' => $connector->config()->all(),
                'cookie' => $connector->client()->manage()->getCookies(),
                ]);

            /**
             * @var PendingRequest $response
             */
            $response = $next($pendingRequest);

            Log::debug('response', [
                'method' => $request->getMethod(),
                'uri' => $pendingRequest->getUrl(),
                'config' => $connector->config()->all(),
                'cookie' => $connector->client()->manage()->getCookies(),
            ]);

            return $response;
        });
    }
}
