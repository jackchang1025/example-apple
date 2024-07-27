<?php

namespace App\Apple\Proxy;

use Illuminate\Contracts\Container\Container;

abstract class ProxyFactory
{

    public function __construct(protected Container $container)
    {
    }

    protected abstract function getConfig():array;

    public function get(array $config =  []): ProxyInterface
    {
        if (empty($config['api_model'])) {
            $config['api_model'] = 'flow';
        }

        return $this->container->make($this->getClass($config['api_model']), ['config' => $config]);
    }

    protected function getClass(string $apiModel = 'flow'):string
    {
        if (!in_array($apiModel, array_keys($this->getConfig()))){
            throw new \InvalidArgumentException("api_model must be flow or dynamic");
        }

        return $this->getConfig()[$apiModel];
    }
}
