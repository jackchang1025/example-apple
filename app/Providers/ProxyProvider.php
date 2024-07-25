<?php

namespace App\Providers;

use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyManager;
use App\Models\ProxyConfiguration;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
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
        $this->app->singleton(ProxyInterface::class, function (Application $app) {

            $config = ProxyConfiguration::where('is_active', true)->firstOrFail();

            $defaultDriver = $config->configuration['default_driver'] ?? null;

            if (!$defaultDriver) {
                Log::warning('No default driver specified in active configuration. Using fallback driver.');

                return $app->make(ProxyManager::class)->driver();
            }

            return $app->make(ProxyManager::class)->driver($defaultDriver);
        });
    }
}
