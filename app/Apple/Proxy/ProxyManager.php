<?php

namespace App\Apple\Proxy;

use Illuminate\Support\Manager;

class ProxyManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'flow';
    }

    public function createFlowDriver(): ProxyInterface
    {
        return new FlowProxy($this->config->get('proxy.stores.flow'));
    }

    public function createDynamicDriver(): ProxyInterface
    {
        return new DynamicProxy($this->config->get('proxy.stores.dynamic'));
    }

}
