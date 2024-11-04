<?php

namespace Modules\IpProxyManager\Service;

use Illuminate\Support\Collection;
use Saloon\Repositories\ArrayStore;

abstract class BaseDto extends ArrayStore
{
    private Collection $proxyList;

    public function setProxyList(Collection $proxyList): static
    {
        $this->proxyList = $proxyList;
        return $this;
    }

    public function getProxyList(): Collection
    {
        return $this->proxyList;
    }

    abstract public function toQueryParameters():array;
}
