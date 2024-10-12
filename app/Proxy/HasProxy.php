<?php

namespace App\Proxy;

use Weijiajia\ProxyResponse;

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

    public function getOrCreateProxyResponse(): ?ProxyResponse
    {
        if (!$this->isProxyEnabled()) {
            return null;
        }

        try {

            return self::$cachedProxyResponse ??= $this->proxy->getProxy($this->getOption());

        } catch (\Exception $e) {

            $this->logger->error("Failed to refresh proxy response: {$e}");
            return null;
        }
    }



    public function refreshProxyResponse(): ?ProxyResponse
    {
        try {

            return self::$cachedProxyResponse = $this->proxy->getProxy($this->getOption());
        } catch (\Exception $e) {

            $this->logger->error("Failed to refresh proxy response: {$e}");
            return null;
        }
    }

    public function setProxy(ProxyResponse $proxy): static
    {
        $this->proxy = $proxy;
        return $this;
    }

    public function getProxy(): ProxyResponse
    {
        return $this->proxy;
    }
}
