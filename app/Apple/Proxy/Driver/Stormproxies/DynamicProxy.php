<?php

namespace App\Apple\Proxy\Driver\Stormproxies;

use App\Apple\Proxy\Exception\ProxyException;
use App\Apple\Proxy\Option;
use App\Apple\Proxy\Proxy;
use App\Apple\Proxy\ProxyModeInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\HttpFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DynamicProxy implements ProxyModeInterface
{
    const string PROXY_HOST = 'https://api.stormproxies.cn/web_v1/ip/get-ip';

    private array $defaultConfig = [
        'ep'       => 'hk',//选择代理网络(代理网络是指中转服务器的位置)
        'cc'       => 'cn',//选择节点国家：cn/us/kr
        'num'      => 1,//单次提取IP数量,最大500
        'city'     => '',//例如阿灵顿：Arlington
        'state'    => '',//州代码
        'life'     => 60,//提取IP时间,最大7200
        'protocol' => 'http',//代理协议,1.http/https/socks5
        'format'   => 2,//返回数据格式,1.txt 2.json
        'lb'       => 1,//分隔符,1.换行回车（\r\n） 2.换行（\n） 3.回车（\r） 4.Tab（\t）
    ];

    public function __construct(protected HttpFactory $httpFactory,array $config = [])
    {
        //字段转换
        $config['ep'] ??= $config['host'] ?? '';
        $config['cc'] ??= $config['area'] ?? '';
        $this->defaultConfig = array_merge($this->defaultConfig, $config);

        if (empty($this->defaultConfig['app_key'])) {
            throw new \InvalidArgumentException("app_key is required for dynamic proxy");
        }
    }


    /**
     * @param Option $option
     * @return ProxyResponse
     * @throws ProxyException
     * @throws ConnectionException
     */
    public function getProxy(Option $option): ProxyResponse
    {
        $config = array_merge($this->defaultConfig, $option->all());

        // Build request URL
        $url = self::PROXY_HOST.'?'.http_build_query($config);

        // Send HTTP GET request
        $response = $this->httpFactory->create()
            ->retry(Proxy::MAX_RETRIES,Proxy::RETRY_DELAY)
            ->connectTimeout(Proxy::VALIDATION_TIMEOUT)
            ->get($url);

        // Handle response
        if ($response->successful()) {
            if (empty($url = $response->json()['data']['list'][0])) {
                throw new ProxyException(sprintf('Failed to get proxy: %s', $response->json()));
            }

            list($host, $port) = explode(':', $url);

            return new ProxyResponse([
                'host' => $host,
                'port' => $port,
                'url'  => $url,
            ]);
        } else {
            throw new ProxyException('Failed to get proxy: '.$response->status().' - '.$response->body());
        }
    }

    public function getProxyIp(ProxyResponse $proxyResponse): string
    {
        return $proxyResponse->getHost();
    }
}
