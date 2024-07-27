<?php

namespace App\Apple\Proxy\Hailiangip;

use App\Apple\Proxy\ProxyFactory;

class HailiangipFactory extends ProxyFactory
{

    protected function getConfig(): array
    {
        return [
            'flow' => FlowProxy::class,
            'dynamic'=>DynamicProxy::class
        ];
    }
}
