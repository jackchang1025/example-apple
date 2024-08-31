<?php

namespace App\Providers;

use App\Apple\Apple;
use Illuminate\Http\Request;
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

            $request = $this->app->get(Request::class);

            if (!$clientId = $request->cookie('Guid',$request->input('Guid'))) {
                throw new \InvalidArgumentException('Guid is not set.');
            }

            return new Apple(
                cache: $this->app->get(CacheInterface::class),
                logger: $this->app->get(LoggerInterface::class),
                clientId: $clientId
            );
        });

    }
}
