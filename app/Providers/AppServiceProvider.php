<?php

namespace App\Providers;

use App\Filament\Widgets\PageVisitsTable;
use App\Hook\AutoRefreshTableWidgetHook;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\Widgets\View\WidgetsRenderHook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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

        RateLimiter::for('api_rate_limiter', function (Request $request) {
            return Limit::perMinute(2)->by( $request->ip())->response(function () {
                return response('Too many attempts, please try again later.', 429);
            });
        });

        Table::$defaultDateTimeDisplayFormat = 'Y-m-d H:i:s';

        //hook PageVisitsTable table auto refresh
        FilamentView::registerRenderHook(
            name: WidgetsRenderHook::TABLE_WIDGET_START,
            hook: static fn() => AutoRefreshTableWidgetHook::render(PageVisitsTable::$pollingInterval),
            scopes: [PageVisitsTable::class]
        );

    }
}
