<?php

namespace App\Apple\Proxy;

use App\Apple\Proxy\Exception\ProxyConfigurationNotFoundException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;

class ProxyFactory
{
    public function __construct(protected Container $container,protected ProxyConfiguration $config)
    {
    }

    /**
     * @throws BindingResolutionException
     */
    public function create(?ProxyConfiguration $config = null): ProxyInterface
    {
        $config = $config ?? $this->config;

        return $this->container->make($config->getDefaultDriverClass(), ['config' => $config]);
    }
}
