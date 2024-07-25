<?php

namespace App\Apple\Proxy;

use App\Models\ProxyConfiguration;
use Illuminate\Support\Manager;

class ProxyManager extends Manager
{
    public function getDefaultDriver()
    {
        $activeConfig = ProxyConfiguration::where('is_active', true)->first();
        return $activeConfig ? $activeConfig->configuration['default_driver'] : 'flow';
    }

    public function createFlowDriver():ProxyInterface
    {
        $config = ProxyConfiguration::where('is_active', true)->first();
        return new FlowProxy($config ? $config->configuration['flow'] : []);
    }

    public function createDynamicDriver():ProxyInterface
    {
        $config = ProxyConfiguration::where('is_active', true)->first();
        return new DynamicProxy($config ? $config->configuration['dynamic'] : []);
    }

}
