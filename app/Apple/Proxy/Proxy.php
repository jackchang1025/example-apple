<?php

namespace App\Apple\Proxy;

use Illuminate\Support\Facades\Http;

abstract class Proxy implements ProxyInterface
{
    /**
     * @param string $proxy
     * @return string|null
     * @throws \Illuminate\Http\Client\ConnectionException
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
