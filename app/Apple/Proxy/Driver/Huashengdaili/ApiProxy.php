<?php

namespace App\Apple\Proxy\Driver\Huashengdaili;

use App\Apple\Proxy\Exception\ProxyException;
use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyModeInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * API代理类
 */
class ApiProxy implements ProxyModeInterface
{
    /**
     * API请求URL
     */
    protected const string API_URL = 'https://mobile.huashengdaili.com/servers.php';

    /**
     * 默认配置
     *
     * @var array
     */
    protected array $config = [
        'time' => 30,         // 提取的IP时长（分钟）
        'count' => 1,         // 提取的IP数量
        'type' => 'json',     // 返回类型
        'only' => 1,          // 是否去重（1=去重，0=不去重）
        'province' => '',     // 省份编号
        'city' => '',         // 城市编号
        'iptype' => 'direct', // IP类型（tunnel=隧道，direct=直连）
        'pw' => 'no',         // 是否需要账号密码（yes=是，no=否）
        'protocol' => 'HTTP', // IP协议（HTTP=HTTP/HTTPS，s5=socks5）
        'separator' => 1,     // 分隔符样式
        'format' => 'city,time', // 其他返回信息
    ];

    /**
     * 构造函数
     *
     * @param array $config 配置数组
     * @throws \InvalidArgumentException 当session未提供时抛出
     */
    public function __construct(protected HttpFactory $httpFactory,array $config)
    {
        if (empty($config['session'])) {
            throw new \InvalidArgumentException('Huashengdaili session is required');
        }
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取代理
     *
     * @param Option $option 选项
     * @return ProxyResponse
     * @throws \Throwable
     * @throws ProxyException
     */
    public function getProxy(Option $option): ProxyResponse
    {
        return $this->httpFactory->create()
            ->get(self::API_URL, $this->buildQueryParams())
            ->throw(fn(Response $response) => throw new ProxyException('Failed to get proxy from Huashengdaili: ' . $response->body()))
            ->collect()
            ->tap(function (Collection $response) {

                if ($response['status'] !== '0') {
                    throw new ProxyException('Huashengdaili error: ' . ($response['detail'] ?? 'Unknown error'));
                }
                if (empty($response['list'])) {
                    throw new ProxyException('Proxy is empty');
                }
            })
            ->pipe(fn(Collection $response) => $this->createProxyResponse($response['list'][0]));
    }

    public function getProxyIp(ProxyResponse $proxyResponse): string
    {
        return $proxyResponse->getHost();
    }

    /**
     * 构建查询参数
     *
     * @return array
     */
    protected function buildQueryParams(): array
    {
        return $this->config;
    }

    /**
     * 创建代理响应对象
     *
     * @param array $proxyData 代理数据
     * @return ProxyResponse
     * @throws ProxyException 当代理已过期时抛出
     */
    protected function createProxyResponse(array $proxyData): ProxyResponse
    {
        $expireTime = Carbon::parse($proxyData['expire_time'] ?? null);

        if ($expireTime->isPast()) {
            throw new ProxyException('Proxy expired');
        }

        return new ProxyResponse([
            'host' => $proxyData['sever'],
            'port' => $proxyData['port'],
            'username' => $proxyData['user'] ?? null,
            'password' => $proxyData['pw'] ?? null,
            'expire_time' => $expireTime,
            'url' => sprintf('%s:%d', $proxyData['sever'], $proxyData['port']),
        ]);
    }
}
