<?php

namespace App\Apple\Proxy\Hailiangip;

use App\Apple\Proxy\Exception\ProxyException;
use App\Apple\Proxy\Option;
use App\Apple\Proxy\Proxy;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DynamicProxy extends Proxy implements ProxyInterface
{
    const string GET_IP_HOST = 'http://api.hailiangip.com:8422';

    private array $defaultConfig = [
        'type' => 1,
        'num' => 1,
        'pid' => -1,
        'unbindTime' => 60,
        'cid' => '',
        'noDuplicate' => 0,
        'dataType' => 0,
        'lineSeparator' => 0,
        'singleIp' => 0,
    ];

    public function __construct(array $config =  [])
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);

        if (empty($this->defaultConfig['orderId'])) {
            throw new \InvalidArgumentException("orderId is required for dynamic proxy");
        }

        if (empty($this->defaultConfig['secret'])) {
            throw new \InvalidArgumentException("secret is required for dynamic proxy");
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

        if (!isset($config['orderId']) || !isset($config['secret'])) {
            throw new \InvalidArgumentException("orderId and secret are required for dynamic proxy");
        }

        $time = time();
        $sign = strtolower(md5("orderId={$config['orderId']}&secret={$config['secret']}&time={$time}"));

        $queryParams = array_merge($config, [
            'time' => $time,
            'sign' => $sign,
        ]);

        $url = self::GET_IP_HOST . "/api/getIp?" . http_build_query($queryParams);

        $response = Http::retry(5,100)->get($url);
        $data = $response->json();

        if (!isset($data['data'][0]['ip']) || !isset($data['data'][0]['port'])) {
            throw new ProxyException('Failed to get dynamic proxy: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return new ProxyResponse([
            'host' => $data['data'][0]['ip'],
            'port' => $data['data'][0]['port'],
            'url' => "http://{$data['data'][0]['ip']}:{$data['data'][0]['port']}",
        ]);
    }
}
