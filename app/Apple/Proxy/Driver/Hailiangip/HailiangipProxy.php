<?php

namespace App\Apple\Proxy\Driver\Hailiangip;

use App\Apple\Proxy\Driver\ProxyModeFactory;
use App\Apple\Proxy\Exception\ProxyModelNotFoundException;
use App\Apple\Proxy\Proxy;
use App\Apple\Proxy\ProxyConfiguration;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyModeInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

class HailiangipProxy extends Proxy implements ProxyInterface
{

    /**
     * @param ProxyConfiguration $config
     * @param ProxyModeFactory $modeFactory
     * @throws BindingResolutionException
     * @throws ProxyModelNotFoundException
     */
    public function __construct(protected ProxyConfiguration $config,protected ProxyModeFactory $modeFactory)
    {
        $this->mode = $this->modeFactory->createMode($this->config);
    }

    public function getMode(): ProxyModeInterface
    {
        return $this->mode;
    }
}
