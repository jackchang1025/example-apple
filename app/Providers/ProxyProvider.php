<?php

namespace App\Providers;

use App\Apple\Proxy\ProxyManager;
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
        $this->app->singleton(ProxyManager::class, function ($app) {
            return new ProxyManager($app);
        });
    }
}
