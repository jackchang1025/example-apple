<?php

namespace App\Apple\Proxy;

use App\Apple\Proxy\Hailiangip\HailiangipFactory;
use App\Apple\Proxy\Stormproxies\StormproxiesFactory;
use App\Models\ProxyConfiguration;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;

class ProxyManager extends Manager
{

    public function __construct(Container $container,protected HailiangipFactory $hailiangipFactory,protected StormproxiesFactory $stormproxiesFactory)
    {
        parent::__construct($container);
    }

    public function getDefaultDriver(): ProxyInterface
    {
        $activeConfig = ProxyConfiguration::where('is_active', true)->first();

        $defaultDriver = $activeConfig->configuration['default_driver'] ?? 'hailiangip';

        return $this->hailiangipFactory->get($activeConfig->configuration[$defaultDriver]);
    }

    public function createHailiangipDriver():ProxyInterface
    {
        $activeConfig = ProxyConfiguration::where('is_active', true)
            ->firstOrFail();

        $defaultDriver = $activeConfig->configuration['default_driver'];

        return $this->hailiangipFactory->get($activeConfig->configuration[$defaultDriver]);
    }

    public function createStormproxiesDriver():ProxyInterface
    {
        $activeConfig = ProxyConfiguration::where('is_active', true)
            ->firstOrFail();

        $defaultDriver = $activeConfig->configuration['default_driver'];

        return $this->stormproxiesFactory->get($activeConfig->configuration[$defaultDriver]);
    }



}
