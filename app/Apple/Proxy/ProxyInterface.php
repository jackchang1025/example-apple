<?php

namespace App\Apple\Proxy;

interface ProxyInterface
{
    public function getProxy(Option $option): string;
}
