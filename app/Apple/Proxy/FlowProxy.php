<?php

namespace App\Apple\Proxy;

class FlowProxy implements ProxyInterface
{
    const string PROXY_HOST = "flow.hailiangip.com";
    const int HTTP_PROXY_PORT = 14223;

    private array $defaultConfig = [
        'mode' => 0,
        'pid' => "-1",
        'cid' => "-1",
        'sip' => 0,
        'uid' => "",
    ];

    public function __construct(array $config =  [])
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);

        if (empty($this->defaultConfig['orderId'])) {
            throw new \Exception("请配置代理账号");
        }
        if (empty($this->defaultConfig['pwd'])) {
            throw new \Exception("请配置代理密码");
        }
    }

    public function getProxy(Option $option): string
    {
        $config = array_merge($this->defaultConfig, $option->all());
        $username = $config['orderId'];
        $password = $this->sign($config);

        return 'http://' . $username . ':' . $password . '@' . self::PROXY_HOST . ':' . self::HTTP_PROXY_PORT;
    }

    protected function sign(array $config): string
    {
        $params = $config['mode'] == 0
            ? ['pwd', 'pid', 'cid', 'uid', 'sip']
            : ['pwd', 'zoneId'];

        return http_build_query(array_intersect_key($config, array_flip($params)));
    }
}
