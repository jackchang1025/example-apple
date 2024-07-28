<?php

namespace App\Apple\Proxy;

use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class Proxy implements ProxyInterface
{
    /**
     * @param ProxyResponse $proxyResponse
     * @return string|null
     * @throws ConnectionException
     */
    public function getProxyIp(ProxyResponse $proxyResponse):?string
    {
        $response =  Http::retry(3,100)->withOptions([
            'proxy' => $proxyResponse->getUrl(),
            'verify' => false,
        ])->get(url('/ip'));

        Log::info(sprintf('get getProxyIp url is %s proxy ip is %s',$proxyResponse->getUrl(),$response->body()));

        return $response->body();
    }
}
