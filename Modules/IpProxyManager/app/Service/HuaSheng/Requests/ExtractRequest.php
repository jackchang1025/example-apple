<?php

namespace Modules\IpProxyManager\Service\HuaSheng\Requests;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\IpProxyManager\Service\BaseDto;
use Modules\IpProxyManager\Service\Exception\ProxyException;
use Modules\IpProxyManager\Service\HuaSheng\DTO\ExtractDto;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class ExtractRequest extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(ExtractDto $dto)
    {
        parent::__construct($dto);

        if (empty($this->dto->get('session'))) {
            throw new \InvalidArgumentException("请配置代理 session");
        }
    }

    /**
     * @param Response $response
     * @return BaseDto
     * @throws \JsonException|ProxyException
     */
    public function createDtoFromResponse(Response $response): BaseDto
    {
        $data = $response->json();
        if (!isset($data['status']) || $data['status'] !== '0' || empty($data['list'])) {
            throw new ProxyException(response: $response, message: $response->body());
        }

        $this->dto->setProxyList((new Collection($data['list']))->map(fn(array $item) => new ProxyResponse(
            host: $item['sever'] ?? null,
            port: $item['port'] ?? null,
            user: $item['user'] ?? null,
            password: $item['password'] ?? null,
            url: "http://{$item['sever']}:{$item['port']}",
            expireTime: isset($item['expire_time']) ? Carbon::parse($item['expire_time']) : null,
        )));

        return $this->dto;
    }
    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/servers.php';
    }

    protected function defaultQuery(): array
    {
        return $this->dto->toQueryParameters();
    }
}
