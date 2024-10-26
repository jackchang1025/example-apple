<?php

namespace Modules\IpProxyManager\Service\Wandou\Request;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\IpProxyManager\Service\BaseDto;
use Modules\IpProxyManager\Service\Exception\ProxyException;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\Request;
use Modules\IpProxyManager\Service\Wandou\DTO\DynamicDto;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class DynamicRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(DynamicDto $dto)
    {
        parent::__construct($dto);

        if (empty($this->dto->get('app_key'))) {
            throw new \InvalidArgumentException("请配置代理 app_key");
        }
    }

    public function createDtoFromResponse(Response $response): BaseDto
    {
        $data = $response->json();

        if ($data['code'] !== 200) {
            throw new ProxyException($response, $data['msg'] ?? $response->body());
        }

        $this->dto->setProxyList((new Collection($data['data']))->map(function (array $item) {
            return new ProxyResponse(
                host: $item['ip'],
                port: $item['port'],
                url: "http://{$item['ip']}:{$item['port']}",
                expireTime: isset($item['expire_time']) ? Carbon::parse($item['expire_time']) : null
            );
        }));

        return $this->dto;
    }

    public function resolveEndpoint(): string
    {
        return 'dynamic';
    }

    protected function defaultQuery(): array
    {
        return $this->dto->toQueryParameters();
    }
}
