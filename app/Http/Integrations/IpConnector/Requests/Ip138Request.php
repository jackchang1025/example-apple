<?php

namespace App\Http\Integrations\IpConnector\Requests;

use App\Http\Integrations\IpConnector\IpaddressRequest;
use App\Http\Integrations\IpConnector\Responses\Ip138Response;
use App\Http\Integrations\IpConnector\Responses\IpResponse;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class Ip138Request extends IpaddressRequest
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(protected string $token,protected string $ip,protected string $dataType = 'jsonp')
    {
    }


    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return 'http://api.ipshudi.com/ipdata/';
    }

    /**
     * @param Response $response
     * @return bool|null
     * @throws \JsonException
     */
    public function hasRequestFailed(Response $response): ?bool
    {
        if ($response->json()['ret'] === 'ok'){
            return false;
        }
        return true;
    }

    public function defaultHeaders():array
    {
        return [
            'token' => $this->token,
        ];
    }

    public function defaultQuery ():array
    {
        return [
            'ip' => $this->ip,
            'datatype' => $this->dataType,
        ];
    }

    public function extractJsonFromString(Response $response): IpResponse
    {
        return new Ip138Response($response->json());
    }
}
