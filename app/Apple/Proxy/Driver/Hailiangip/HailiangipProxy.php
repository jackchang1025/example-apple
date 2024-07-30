<?php

namespace App\Apple\Proxy\Driver\Hailiangip;

use App\Apple\Proxy\Driver\ProxyModeFactory;
use App\Apple\Proxy\Option;
use App\Apple\Proxy\Proxy;
use App\Apple\Proxy\ProxyConfiguration;
use App\Apple\Proxy\ProxyFactory;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyModeInterface;
use App\Apple\Proxy\ProxyResponse;

class HailiangipProxy extends Proxy implements ProxyInterface
{
    protected ProxyModeInterface $mode;

    public function __construct(protected ProxyConfiguration $config,protected ProxyModeFactory $modeFactory)
    {
        $this->mode = $this->modeFactory->createMode($this->config);
    }

    public function getProxy(Option $option): ProxyResponse
    {
        return $this->mode->getProxy($option);
    }
}
