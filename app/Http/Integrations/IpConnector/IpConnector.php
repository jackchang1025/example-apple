<?php

namespace App\Http\Integrations\IpConnector;

use App\Http\Integrations\IpConnector\Responses\IpResponse;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

class IpConnector extends Connector
{

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return '';
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [];
    }

    /**
     * Default HTTP client options
     */
    protected function defaultConfig(): array
    {
        return [];
    }

    public function boot(PendingRequest $pendingRequest):void
    {
        $pendingRequest->middleware()->onRequest(function (PendingRequest $pendingRequest){
            Log::debug('Request', [
                'method'  => $pendingRequest->getMethod(),
                'uri'     => (string) $pendingRequest->getUri(),
                'headers' => $pendingRequest->headers(),
                'body'    => (string)$pendingRequest->body(),
            ]);
        });

        $pendingRequest->middleware()->onResponse(function (Response $response){
            Log::debug('response', [
                'status'  => $response->status(),
                'headers' => $response->headers(),
                'body'    => $response->body(),
            ]);
        });

    }

    /**
     * @param IpaddressRequest|null $request
     * @return IpResponse
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function ipaddress(?IpaddressRequest $request): Responses\IpResponse
    {
        return $request->extractJsonFromString($this->send($request));
    }

}
