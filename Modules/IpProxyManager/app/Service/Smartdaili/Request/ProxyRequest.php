<?php

namespace Modules\IpProxyManager\Service\Smartdaili\Request;

use Illuminate\Support\Collection;
use Modules\IpProxyManager\Service\BaseDto;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\Request;
use Modules\IpProxyManager\Service\Smartdaili\DTO\ProxyDto;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

class ProxyRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(ProxyDto $dto)
    {
        parent::__construct($dto);
        $this->validateConfig();

    }

    protected function validateConfig(): void
    {
        $requiredFields = [
            'username' => '用户名',
            'password' => '密码',
            'endpoint' => '服务器地址',
            'port' => '端口',
        ];

        foreach ($requiredFields as $field => $name) {
            if (empty($this->dto->get($field))) {
                throw new \InvalidArgumentException("请配置{$name}");
            }
        }

        // 验证端口
        if (!is_numeric($this->dto->get('port')) || $this->dto->get('port') <= 0) {
            throw new \InvalidArgumentException('端口配置无效');
        }

        // 验证协议
        $protocol = $this->dto->get('protocol', 'http');
        if (!in_array($protocol, ['http', 'socks5'])) {
            throw new \InvalidArgumentException('不支持的协议类型: '.$protocol);
        }
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $mockResponse = $this->buildProxyResponse();

        $mockClient = new MockClient([
            __CLASS__ => MockResponse::make(body: $mockResponse),
        ]);

        $pendingRequest->withMockClient($mockClient);
    }

    protected function buildProxyResponse(): array
    {
        $username = $this->dto->get('username');
        $password = $this->dto->get('password');
        $endpoint = $this->dto->get('endpoint');
        $port = $this->dto->get('port');
        $protocol = $this->dto->get('protocol', 'http');


        return [
            'username' => $username,
            'password' => $password,
            'host' => $endpoint,
            'port' => $port,
            'url'  => $this->buildProxyUrl($username, $password, $endpoint, $port, $protocol),
        ];
    }

    protected function buildProxyUrl(
        string $username,
        string $password,
        string $host,
        int $port,
        string $protocol
    ): string {
        return sprintf(
            '%s://%s:%s@%s:%d',
            $protocol,
            $username,
            $password,
            $host,
            $port
        );
    }

    public function resolveEndpoint(): string
    {
        return 'proxy';
    }

    protected function defaultQuery(): array
    {
        return $this->dto->toQueryParameters();
    }

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
}
