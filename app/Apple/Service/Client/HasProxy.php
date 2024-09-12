<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;

trait HasProxy
{
    protected static ?ProxyResponse $cachedProxyResponse = null;

    protected ProxyInterface $proxy;
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

    public function getProxyResponse(): ?ProxyResponse
    {
        return self::$cachedProxyResponse;
    }

    public function getOrCreateProxyResponse(): ?ProxyResponse
    {
        if (!$this->isProxyEnabled()) {
            return null;
        }

        return self::$cachedProxyResponse ??= $this->proxy->getProxy($this->getOption());
    }

    public function setProxyResponse(?ProxyResponse $proxyResponse): static
    {
        self::$cachedProxyResponse = $proxyResponse;
        return $this;
    }

    public function refreshProxyResponse(): ProxyResponse
    {
        return self::$cachedProxyResponse = $this->proxy->getProxy($this->getOption());
    }

    public function setProxy(ProxyInterface $proxy): static
    {
        $this->proxy = $proxy;
        return $this;
    }

    public function getProxy(): ProxyInterface
    {
        return $this->proxy;
    }
}
