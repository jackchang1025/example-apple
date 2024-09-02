<?php

namespace App\Http\Integrations\Proxy\HuaSheng\Requests;

use App\Http\Integrations\Proxy\BaseDto;
use App\Http\Integrations\Proxy\HuaSheng\DTO\ExtractDto;
use App\Http\Integrations\Proxy\ProxyResponse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class Extract extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(protected ExtractDto $dto)
    {
        if (empty($this->dto->get('session'))) {
            throw new \InvalidArgumentException("请配置代理 session");
        }
    }

    /**
     * @param Response $response
     * @return BaseDto
     * @throws \JsonException
     */
    public function createDtoFromResponse(Response $response): BaseDto
    {
        return $this->dto->setProxyList((new Collection($response->json()))->map(fn(array $item) => new ProxyResponse(
            host: $item['sever'] ?? null,
            port: $item['port'] ?? null,
            user: $item['user'] ?? null,
            password: $item['password'] ?? null,
            expireTime: isset($item['expire_time']) ? Carbon::parse($item['expire_time']) : null,
        )));
    }

    /**
     * @param Response $response
     * @return bool|null
     * @throws \JsonException
     */
    public function hasRequestFailed(Response $response): ?bool
    {
        $data = $response->json();
        if ($data['status'] !== '0' || empty($data['list'])) {
            return true;
        }

        return null;
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
