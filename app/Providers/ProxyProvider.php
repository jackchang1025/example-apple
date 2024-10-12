<?php

namespace App\Providers;

use App\Proxy\ProxyFactory;
use App\Proxy\ProxyService;
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
        $this->app->singleton(ProxyService::class, function () {

            return $this->app->get(ProxyFactory::class)->create();
        });
    }
}
