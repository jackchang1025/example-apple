<?php

namespace Modules\IpAddress\Service\Requests;

use JsonException;
use Modules\IpAddress\Service\Exception\IpLookupException;
use Modules\IpAddress\Service\Responses\IpResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class Ip138Request extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(protected string $token, protected string $ip, protected string $dataType = 'jsonp')
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
     * @throws JsonException
     */
    public function hasRequestFailed(Response $response): ?bool
    {
        return $response->json()['ret'] !== 'ok';
    }

    public function defaultHeaders(): array
    {
        return [
            'token' => $this->token,
        ];
    }

    public function defaultQuery(): array
    {
        return [
            'ip'       => $this->ip,
            'datatype' => $this->dataType,
        ];
    }

    /**
     * @param Response $response
     * @return IpResponse
     * @throws IpLookupException
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): IpResponse
    {
        $json = $response->json();
        if (!isset($json['data']) || !is_array($json['data']) || count($json['data']) < 3) {
            throw new IpLookupException($response, $response->body());
        }

        return new IpResponse([
            'city' => $response->json('data')[2],
            'addr' => $response->json('addr'),
            'ip'   => $response->json('ip'),
        ]);
    }
}
