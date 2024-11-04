<?php

namespace Modules\IpProxyManager\Service\Wandou\Request;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\IpProxyManager\Service\BaseDto;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\Request;
use Modules\IpProxyManager\Service\Wandou\DTO\AccountPasswordDto;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

class AccountPasswordRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(AccountPasswordDto $dto)
    {
        parent::__construct($dto);

        if (empty($this->dto->get('username'))) {
            throw new \InvalidArgumentException("请配置代理用户名");
        }
        if (empty($this->dto->get('password'))) {
            throw new \InvalidArgumentException("请配置代理密码");
        }
        if (empty($this->dto->get('host'))) {
            throw new \InvalidArgumentException("请配置代理网络");
        }
        if (empty($this->dto->get('port'))) {
            throw new \InvalidArgumentException("请配置代理网络端口");
        }
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $username = $this->dto->get('username');

        $data = Arr::only($this->dto->all(), [
            'life',
            'pid',
            'cid',
            'isp',
        ]);

        if ($city = $this->dto->get('city')) {
            $data['cid'] = $city;
        }
        if ($province = $this->dto->get('province')) {
            $data['pid'] = $province;
        }

        $data['session'] = time();

//        $data = array_filter($this->dto->all(), static fn($value) => $value !== null);

        foreach ($data as $key => $value) {
            $username .= sprintf("_%s-%s", $key, $value);
        }

        //curl -x db1z2pgm_session-23424_life-2_pid-0_isp-1:o5njazji@gw.wandouapp.com:1000 api.ip.cc
        //curl -x db1z2pgm_session-17299_life-5_isp-0_pid-0:o5njazji@gw.wandouapp.com:1000 api.ip.cc

        // Create a mock client for the flow proxy
        $mockClient = new MockClient([
            __CLASS__ => MockResponse::make(
                body: [
                    'username' => $username,
                    'password' => $this->dto->get('password'),
                    'host'     => $this->dto->get('host'),
                    'port'     => $this->dto->get('port'),
                    'url'      => sprintf(
                        'http://%s:%s@%s:%d',
                        $username,
                        $this->dto->get('password'),
                        $this->dto->get('host'),
                        $this->dto->get('port')
                    ),
                ]
            ),
        ]);

        $pendingRequest->withMockClient($mockClient);
    }

    /**
     * @param Response $response
     * @return BaseDto
     * @throws \JsonException
     */
    public function createDtoFromResponse(Response $response): BaseDto
    {
        $data = $response->json();

        $result = (new Collection())->push(
            new ProxyResponse(
                host: $data['host'] ?? null,
                port: $data['port'] ?? null,
                user: $data['username'] ?? null,
                password: $data['password'] ?? null,
                url: $data['url'] ?? null,
            )
        );

        $this->dto->setProxyList($result);

        return $this->dto;
    }

    public function resolveEndpoint(): string
    {
        return 'proxy';
    }

    protected function defaultQuery(): array
    {
        return $this->dto->toQueryParameters();
    }
}
