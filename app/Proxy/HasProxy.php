<?php

namespace App\Proxy;


use Weijiajia\IpProxyManager\ProxyResponse;

trait HasProxy
{
    protected ?ProxyResponse $proxy = null;

    protected bool $proxyEnabled = false;
    protected bool $ipaddressEnabled = false;

    public function enableIpaddress(bool $enable = true): static
    {
        $this->ipaddressEnabled = $enable;
        return $this;
    }

    public function isIpaddressEnabled(): bool
    {
        return $this->ipaddressEnabled;
    }

    public function enableProxy(bool $enable = true): static
    {
        $this->proxyEnabled = $enable;
        return $this;
    }

    public function isProxyEnabled(): bool
    {
        return $this->proxyEnabled;
    }
}
