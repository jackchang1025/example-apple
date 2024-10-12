<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Weijiajia\AppleClient;
use Weijiajia\AppleClientFactory;

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
        $this->app->singleton(AppleClient::class, function () {

            $appleFactory = $this->app->get(AppleClientFactory::class);

            $request = $this->app->get(Request::class);

            $guid = $request->input('Guid',$request->cookie('Guid'));
            if (!$guid) {
                throw new \InvalidArgumentException('Guid is not set.');
            }

            return $appleFactory->create(clientId: $guid,config: config('apple'));
        });

    }
}
