<?php

namespace App\Http\Integrations\Proxy\Stormproxies\Request;

use App\Http\Integrations\Proxy\BaseDto;
use App\Http\Integrations\Proxy\ProxyResponse;
use App\Http\Integrations\Proxy\Stormproxies\DTO\DynamicDto;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class DynamicMode extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(public DynamicDto $dto)
    {
        if (empty($this->dto->get('app_key'))) {
            throw new \InvalidArgumentException("请配置代理 key");
        }
    }

    /**
     * @param Response $response
     * @return mixed
     * @throws \JsonException
     */
    public function createDtoFromResponse(Response $response): BaseDto
    {
        return $this->dto->setProxyList((new Collection($response->json()['data']['list'] ?? []))->map(function(string $item){

            list($host, $port) = explode(':', $item);

            return new ProxyResponse(
                host: $host,
                port: $port,
                url: $item,
            );
        }));
    }

    public function hasRequestFailed(Response $response): ?bool
    {
        $data = $response->json();
        if (empty($data['data']['list'])){
            return true;
        }
        return null;
    }

    protected function defaultQuery(): array
    {
        return $this->dto->toQueryParameters();
    }


    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/web_v1/ip/get-ip';
    }
}
