<?php

namespace App\Apple\Proxy\Stormproxies;

use App\Apple\Proxy\ProxyFactory;

class StormproxiesFactory extends ProxyFactory
{

    protected function getConfig(): array
    {
        return [
            'flow' => FlowProxy::class,
            'dynamic'=>DynamicProxy::class
        ];
    }
}
