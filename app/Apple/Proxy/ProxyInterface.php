<?php

namespace App\Apple\Proxy;

interface ProxyInterface
{
    public function getProxy(Option $option): ProxyResponse;

    public function getProxyIp(ProxyResponse $proxyResponse):?string;
}
