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
    public function getProxyIp(string $proxy):?string
    {
        $response = Http::withOptions([
            'proxy' => $proxy,
            'verify' => false,
        ])->retry(5,100)->get('http://httpbin.org/ip');

        return $response->json()['origin'] ?? null;
    }
}
