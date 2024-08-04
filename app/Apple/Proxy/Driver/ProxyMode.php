<?php

namespace App\Apple\Proxy\Driver;
use App\Apple\Proxy\ProxyModeInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\HttpFactory;
use Illuminate\Http\Client\ConnectionException;

abstract class ProxyMode implements ProxyModeInterface
{
    /**
     * 最大重试次数
     */
    public const int MAX_RETRIES = 5;

    /**
     * 重试延迟（毫秒）
     */
    public const int RETRY_DELAY = 100;

    public function __construct(protected HttpFactory $httpFactory)
    {

    }


    /**
     * @param ProxyResponse $proxyResponse
     * @return string
     * @throws ConnectionException
     */
    public function getProxyIp(ProxyResponse $proxyResponse):string
    {
        return $this->httpFactory->create()
            ->retry(self::MAX_RETRIES,self::RETRY_DELAY)
            ->withOptions([
                'proxy' => $proxyResponse->getUrl(),
                'verify' => false,
            ])
            ->get(url('/ip'))
            ->body();
    }
}
