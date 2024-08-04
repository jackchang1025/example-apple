<?php

namespace App\Apple\Proxy;


interface ProxyModeInterface
{
    public function getProxy(Option $option): ProxyResponse;

    public function getProxyIp(ProxyResponse $proxyResponse):string;
}
