<?php

namespace App\Apple\Proxy\Stormproxies;

use App\Apple\Proxy\Exception\ProxyException;
use App\Apple\Proxy\Option;
use App\Apple\Proxy\Proxy;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DynamicProxy extends Proxy implements ProxyInterface
{
    const string PROXY_HOST = 'https://api.stormproxies.cn/web_v1/ip/get-ip';

    private array $defaultConfig = [
        'cc'       => '',//(可选): 例如俄罗斯：RU
        'num'      => 1,//单次提取IP数量,最大500
        'city'     => '',//例如阿灵顿：Arlington
        'state'    => '',//州代码
        'life'     => 60,//提取IP时间,最大7200
        'protocol' => 'http',//代理协议,1.http/https/socks5
        'format'   => 2,//返回数据格式,1.txt 2.json
        'lb'       => 1,//分隔符,1.换行回车（\r\n） 2.换行（\n） 3.回车（\r） 4.Tab（\t）
    ];

    public function __construct(array $config = [])
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);

        if (empty($this->defaultConfig['app_key'])) {
            throw new \InvalidArgumentException("app_key is required for dynamic proxy");
        }

//        if (empty($this->defaultConfig['pt'])) {
//            throw new \InvalidArgumentException("pt is required for dynamic proxy");
//        }
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
        $response = Http::retry(5, 100)->get($url);

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
}
