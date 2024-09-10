<?php

namespace App\Providers;

use Apple\Client\Apple;
use Apple\Client\AppleFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class AppleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(Apple::class, function () {

            $appleFactory = $this->app->get(AppleFactory::class);

            $request = $this->app->get(Request::class);

            if (!$clientId = $request->cookie('Guid',$request->input('Guid'))) {
                throw new \InvalidArgumentException('Guid is not set.');
            }

            return $appleFactory->create(clientId: $clientId,config: config('apple'));
        });

    }
}
