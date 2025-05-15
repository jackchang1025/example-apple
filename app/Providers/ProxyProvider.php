<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ProxyConfiguration;
use Illuminate\Contracts\Config\Repository;
use Weijiajia\HttpProxyManager\ProxyManager;

class ProxyProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->singleton(ProxyManager::class, function ($app) {

            $config = $this->app->make(Repository::class);

            $proxyConfiguration = ProxyConfiguration::first();

            $httpProxyManagerConfig = $config->get('http-proxy-manager');

            //合并配置
            $mergeConfig = array_merge($httpProxyManagerConfig, $proxyConfiguration->configuration);

            $config->set('http-proxy-manager', $mergeConfig);


            return new ProxyManager($app);
        });
    }
}
