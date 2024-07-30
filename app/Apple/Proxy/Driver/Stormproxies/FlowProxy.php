<?php

namespace App\Apple\Proxy\Driver\Stormproxies;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyModeInterface;
use App\Apple\Proxy\ProxyResponse;

class FlowProxy  implements ProxyModeInterface
{
    const string PROXY_HOST = 'proxy.stormip.cn';

    const int HTTP_PROXY_PORT = 1000;

    private array $defaultConfig = [
        'session' => '',
        'life'    => "30",//保持ip使用的时间,单位分钟，最小1,最大24*60
        'area'    => "US",// 全球地区code 例如：美国 area-US  点击查看
        'city'    => 0,// 所属城市 例如：纽约 city-newyork  点击查看
        'state'   => "",//州代码  点击查看
        'ip'      => "",//指定数据中心地址
        'host'      => self::PROXY_HOST,//代理网络
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

        if (empty($this->defaultConfig['host'])) {
            throw new \InvalidArgumentException("请配置代理网络");
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
            'host'     => $config['host'],
            'port'     => self::HTTP_PROXY_PORT,
            'url'      => sprintf('http://%s:%s@%s:%d', $username,$config['password'],$config['host'], self::HTTP_PROXY_PORT),
        ]);
    }
}
