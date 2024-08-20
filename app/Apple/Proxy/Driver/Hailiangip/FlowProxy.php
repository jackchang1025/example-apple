<?php

namespace App\Apple\Proxy\Driver\Hailiangip;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyModeInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Proxy\Driver\ProxyMode;
use App\Apple\Service\Client\ClientFactory;

class FlowProxy extends ProxyMode implements ProxyModeInterface
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

    public function __construct(ClientFactory $httpFactory,array $config =  [])
    {
        parent::__construct($httpFactory);
        $this->defaultConfig = array_merge($this->defaultConfig, $config);

        if (empty($this->defaultConfig['orderId'])) {
            throw new \InvalidArgumentException("请配置代理账号");
        }
        if (empty($this->defaultConfig['pwd'])) {
            throw new \InvalidArgumentException("请配置代理密码");
        }
    }

    /**
     * @param Option $option
     * @return ProxyResponse
     */
    public function getProxy(Option $option): ProxyResponse
    {
        $config = array_merge($this->defaultConfig, $option->all());

        if (!empty($config['session'])){
            $config['uid'] = $config['session'];
        }

        $username = $config['orderId'];
        $password = $this->sign($config);

        return new ProxyResponse([
            'url' => sprintf('http://%s:%s@%s:%d', $username,$password,self::PROXY_HOST, self::HTTP_PROXY_PORT),
            'username' => $username,
            'password' => $config['pwd'],
            'host' => self::PROXY_HOST,
            'port' => self::HTTP_PROXY_PORT,
        ]);
    }

    protected function sign(array $config): string
    {
        $params = $config['mode'] == 0
            ? ['pwd', 'pid', 'cid', 'uid', 'sip']
            : ['pwd', 'zoneId'];

        return http_build_query(array_intersect_key($config, array_flip($params)));
    }
}
