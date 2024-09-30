<?php

namespace App\Providers;

use App\Selenium\AppleClient\AppleConnectorFactory;
use App\Selenium\ConnectorManager;
use App\Selenium\Repositories\RedisRepository;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\ServiceProvider;

class ConnectorManagerProvider extends ServiceProvider
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
        $this->app->singleton(ConnectorManager::class, function ($app) {

            /**
             * @var RedisManager $redisManager
             */
            $redisManager = $app->make(RedisManager::class);

            $redisRepositories = new RedisRepository($redisManager->client());

            return new ConnectorManager($redisRepositories,$app->make(AppleConnectorFactory::class));

        });
    }
}
