<?php

namespace App\Apple\Proxy\Stormproxies;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\Proxy;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class FlowProxy extends Proxy implements ProxyInterface
{
    const string PROXY_HOST = 'proxy.stormip.cn';

    const int HTTP_PROXY_PORT = 1000;

    private array $defaultConfig = [
        'session' => '',
        'life'    => "30",//保持ip使用的时间,单位分钟，最小1,最大24*60
        'area'    => "",// 全球地区code 例如：美国 area-US  点击查看
        'city'    => 0,// 所属城市 例如：纽约 city-newyork  点击查看
        'state'   => "",//州代码  点击查看
        'ip'      => "",//指定数据中心地址
    ];

    public function __construct(array $config =  [])
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);

        if (empty($this->defaultConfig['username'])) {
            throw new \InvalidArgumentException("请配置代理用户名");
        }
        if (empty($this->defaultConfig['password'])) {
            throw new \InvalidArgumentException("请配置代理密码");
        }
    }

    public function getProxy(Option $option): ProxyResponse
    {
        $config = array_merge($this->defaultConfig, $option->all());

        $params = [
            'session' => $config['session'],
            'life'    => $config['life'],
            'area'    => $config['area'],
            'city'    => $config['city'],
            'state'   => $config['state'],
            'ip'      => $config['ip'],
        ];

        $username = $config['username'];
        foreach (array_filter($params) as $key => $value){
            $username .= sprintf("_%s-%s", $key,$value);
        }

        return new ProxyResponse([
            'username' => $username,
            'password' => $config['password'],
            'host'     => self::PROXY_HOST,
            'port'     => self::HTTP_PROXY_PORT,
            'url'      => sprintf('http://%s:%s@%s:%d', $username,$config['password'],self::PROXY_HOST, self::HTTP_PROXY_PORT),
        ]);
    }

    public function getProxyIp (ProxyResponse $proxyResponse):string
    {

        var_dump($proxyResponse->getUrl());

        $response =  Http::retry(3,100)->withOptions([
            'proxy' => [
                $proxyResponse->getUrl()
            ],
            'verify' => false,
            RequestOptions::HTTP_ERRORS => false,
        ])
            ->get(url('/ip'));

        var_dump($response->status(),$response->body());
    }


}
