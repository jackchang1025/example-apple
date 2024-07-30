<?php
namespace App\Apple\Proxy\Driver;
use App\Apple\Proxy\Exception\ProxyModelNotFoundException;
use App\Apple\Proxy\ProxyConfiguration;
use App\Apple\Proxy\ProxyModeInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;

class ProxyModeFactory
{
    public function __construct(protected Container $container,protected ProxyConfiguration $config)
    {
    }

    /**
     * @param ProxyConfiguration|null $config
     * @return ProxyModeInterface
     * @throws BindingResolutionException
     * @throws ProxyModelNotFoundException
     */
    public function createMode(?ProxyConfiguration $config = null): ProxyModeInterface
    {
        $config = $config ?? $this->config;

        return $this->container->make($config->getDefaultModeClass(), ['config' => $config->getDefaultDriverConfig()]);
    }
}
