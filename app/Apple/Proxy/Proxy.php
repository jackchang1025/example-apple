<?php

namespace App\Apple\Proxy;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

abstract class Proxy implements ProxyInterface
{
    /**
     * @param ProxyResponse $proxyResponse
     * @return string|null
     * @throws ConnectionException
     */
    public function getProxyIp(ProxyResponse $proxyResponse):?string
    {
        return Http::withOptions([
            'proxy' => $proxyResponse->getUrl(),
            'verify' => false,
        ])
            ->retry(5,100)
            ->get(url('/ip'))
            ->body();
    }
}
