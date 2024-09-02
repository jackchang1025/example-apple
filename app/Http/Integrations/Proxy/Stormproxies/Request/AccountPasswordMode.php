<?php

namespace App\Http\Integrations\Proxy\Stormproxies\Request;

use App\Http\Integrations\Proxy\BaseDto;
use App\Http\Integrations\Proxy\ProxyResponse;
use App\Http\Integrations\Proxy\Stormproxies\DTO\AccountPasswordDto;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

class AccountPasswordMode extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(public AccountPasswordDto $dto)
    {
        if (empty($this->dto->get('username'))) {
            throw new \InvalidArgumentException("请配置代理用户名");
        }
        if (empty($this->dto->get('password'))) {
            throw new \InvalidArgumentException("请配置代理密码");
        }
        if (empty($this->dto->get('host'))) {
            throw new \InvalidArgumentException("请配置代理网络");
        }
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $username = $this->dto->get('username');
        foreach ($this->dto->toQueryParameters() as $key => $value) {
            $username .= sprintf("_%s-%s", $key, $value);
        }

        // Create a mock client for the flow proxy
        $mockClient = new MockClient([
            AccountPasswordMode::class => MockResponse::make(
                body: [
                    'username' => $username,
                    'password' => $this->dto->get('password'),
                    'host'     => $this->dto->get('host'),
                    'port'     => 1000,
                    'url'      => sprintf(
                        'http://%s:%s@%s:%d',
                        $username,
                        $this->dto->get('password'),
                        $this->dto->get('host'),
                        1000
                    ),
                ]
            ),
        ]);

        $pendingRequest->withMockClient($mockClient);
    }

    /**
     * @param Response $response
     * @return mixed
     * @throws \JsonException
     */
    public function createDtoFromResponse(Response $response): BaseDto
    {
        $data = $response->json();

        return $this->dto->setProxyList(
            (new Collection())->push(
                new ProxyResponse(
                    host: $data['host'] ?? null,
                    port: $data['port'] ?? null,
                    user: $data['username'] ?? null,
                    password: $data['password'] ?? null,
                    url: $data['url'] ?? null,
                )
            )
        );
    }


    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '';
    }
}
