<?php

namespace App\Providers;

use App\Listeners\AccountStatusSubscriber;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Event::subscribe(AccountStatusSubscriber::class);

        RateLimiter::for('api_rate_limiter', function (Request $request) {
            return Limit::perMinute(2)->by( $request->ip())->response(function () {
                return response('Too many attempts, please try again later.', 429);
            });
        });

        Table::$defaultDateTimeDisplayFormat = 'Y-m-d H:i:s';

    }
}
