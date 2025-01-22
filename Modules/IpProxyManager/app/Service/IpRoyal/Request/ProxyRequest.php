<?php

namespace Modules\IpProxyManager\Service\IpRoyal\Request;

use Illuminate\Support\Collection;
use Modules\IpProxyManager\Service\BaseDto;
use Modules\IpProxyManager\Service\IpRoyal\DTO\ProxyDto;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\Request;
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

        $proxyType = $this->dto->get('mode');
        if (!$proxyType) {
            throw new \InvalidArgumentException("请选择代理类型");
        }

        // 验证必要的配置
        $this->validateConfig($proxyType);
    }

    protected function validateConfig(string $proxyType): void
    {
        $config = $this->dto->get($proxyType);
        if (!$config) {
            throw new \InvalidArgumentException("缺少 {$proxyType} 配置");
        }

        $requiredFields = [
            'username' => '用户名',
            'password' => '密码',
            'endpoint' => '服务器',
            'port'     => '端口',
        ];

        foreach ($requiredFields as $field => $name) {
            if (!isset($config[$field])) {
                throw new \InvalidArgumentException("请配置{$proxyType}代理{$name}");
            }

            if ($field === 'port' && (!is_numeric($config[$field]) || $config[$field] <= 0)) {
                throw new \InvalidArgumentException("{$proxyType}代理端口配置无效");
            }

            if (in_array($field, ['username', 'password', 'endpoint']) && !is_string($config[$field])) {
                throw new \InvalidArgumentException("{$proxyType}代理{$name}配置类型错误");
            }
        }
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $proxyType = $this->dto->get('mode');
        $config    = $this->dto->get($proxyType);

        // 构建代理响应
        $mockResponse = match ($proxyType) {
            'residential' => $this->buildProxyResponse($config),
            'datacenter' => $this->buildProxyResponse($config),
            'mobile' => $this->buildProxyResponse($config),
            default => throw new \InvalidArgumentException("不支持的代理类型: {$proxyType}")
        };


        $mockClient = new MockClient([
            __CLASS__ => MockResponse::make(body: $mockResponse),
        ]);

        $pendingRequest->withMockClient($mockClient);
    }

    protected function buildProxyResponse(array $config): array
    {
        $protocol = $this->dto->get('protocol', 'http');
        $port     = $config['port'];
        $password = $this->buildPassword($config['password']);

        return [
            'username' => $config['username'],
            'password' => $password,
            'host'     => $config['endpoint'],
            'port'     => $port,
            'url'      => $this->buildProxyUrl($config['username'], $password, $config['endpoint'], $port, $protocol),
        ];
    }

    protected function buildPassword(string $basePassword): string
    {
        $extraParams = [
            'country'       => $this->dto->get('country'),
            'state'         => $this->dto->get('state'),
            'region'        => $this->dto->get('region'),
            'session'       => $this->dto->get('sticky_session') ? $this->generateSessionId() : null,
            'lifetime'      => $this->dto->get('sticky_session') ? sprintf(
                '%dm',
                $this->dto->get('session_duration', 10)
            ) : null,
            'streaming'     => $this->dto->get('streaming') ? '1' : null,
            'skipispstatic' => $this->dto->get('skip_isp_static') ? '1' : null,
            'skipipslist'   => $this->dto->get('skip_ips_list'),
        ];

        // 过滤掉空值
        $params = array_filter($extraParams, fn($value) => !is_null($value));

        // 如果没有额外参数，直接返回原密码
        if (empty($params)) {
            return $basePassword;
        }

        // 构建参数字符串
        $paramStrings = [];
        foreach ($params as $key => $value) {
            $paramStrings[] = sprintf('%s-%s', $key, $value);
        }

        // 使用-连接所有参数，并添加到密码后面
        return $basePassword.'_'.implode('_', $paramStrings);
    }

    protected function generateSessionId(): string
    {
        // 生成8位随机字符串作为会话ID
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
    }

    protected function buildProxyUrl(
        string $username,
        string $password,
        string $host,
        int $port,
        string $protocol
    ): string {
        $protocol = strtolower($protocol);
        if (!in_array($protocol, ['http', 'socks5'])) {
            throw new \InvalidArgumentException("不支持的协议类型: {$protocol}");
        }

        // 构建认证部分
        $auth = sprintf('%s:%s', $username, $password);

        // 构建主机部分
        $hostPart = sprintf('%s:%d', $host, $port);

        // 组合完整的代理URL
        return sprintf('%s://%s@%s', $protocol, $auth, $hostPart);
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
