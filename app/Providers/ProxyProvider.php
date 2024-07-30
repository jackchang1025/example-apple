<?php

namespace App\Providers;

use App\Apple\Proxy\Driver\ProxyModeFactory;
use App\Apple\Proxy\ProxyConfiguration;
use App\Apple\Proxy\ProxyFactory;
use App\Apple\Proxy\ProxyInterface;
use Illuminate\Support\ServiceProvider;

class ProxyProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->singleton(ProxyConfiguration::class, function () {
            return new ProxyConfiguration();
        });

        $this->app->singleton(ProxyFactory::class, function ($app) {
            return new ProxyFactory($app, $app->make(ProxyConfiguration::class));
        });

        $this->app->singleton(ProxyModeFactory::class, function ($app) {
            return new ProxyModeFactory($app, $app->make(ProxyConfiguration::class));
        });

        $this->app->singleton(ProxyInterface::class, function ($app) {
            return $app->make(ProxyFactory::class)->create();
        });
    }
}
