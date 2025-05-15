<?php

namespace App\Providers;

use App\Filament\Widgets\PageVisitsTable;
use App\Hook\AutoRefreshTableWidgetHook;
use App\Listeners\AuthenticatedListener;
use App\Listeners\SignIn\SignInSuccessListener;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\Widgets\View\WidgetsRenderHook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Services\LaravelEventDispatcher;

use Weijiajia\SaloonphpAppleClient\Events\Authenticated\AuthenticatedEvent;
use Weijiajia\SaloonphpAppleClient\Events\SendPhoneSecurityCodeFailedEvent;
use Weijiajia\SaloonphpAppleClient\Events\SecurityPhone\SendPhoneSecurityCodeFailedEvent as SecurityPhoneSendPhoneSecurityCodeFailedEvent;
use Weijiajia\SaloonphpAppleClient\Events\SendPhoneSecurityCodeSuccessEvent;
use Weijiajia\SaloonphpAppleClient\Events\SecurityPhone\SendPhoneSecurityCodeSuccessEvent as SecurityPhoneSendPhoneSecurityCodeSuccessEvent;
use Weijiajia\SaloonphpAppleClient\Events\SendVerificationCodeFailedEvent;
use Weijiajia\SaloonphpAppleClient\Events\SendVerificationCodeSuccessEvent;
use Weijiajia\SaloonphpAppleClient\Events\SignInSuccessEvent;
use Weijiajia\SaloonphpAppleClient\Events\VerifySecurityCodeFailedEvent;
use Weijiajia\SaloonphpAppleClient\Events\SecurityPhone\VerifySecurityCodeFailedEvent as SecurityPhoneVerifySecurityCodeFailedEvent;
use Weijiajia\SaloonphpAppleClient\Events\VerifySecurityCodeSuccessEvent;
use Weijiajia\SaloonphpAppleClient\Events\SecurityPhone\VerifySecurityCodeSuccessEvent as SecurityPhoneVerifySecurityCodeSuccessEvent;

use App\Listeners\SecurityPhone\SendPhoneSecurityCodeFailListener as SecurityPhoneSendPhoneSecurityCodeFailListener;
use App\Listeners\SecurityPhone\SendPhoneSecurityCodeSuccessListener as SecurityPhoneSendPhoneSecurityCodeSuccessListener;
use App\Listeners\SecurityPhone\VerifySecurityCodeFailListener as SecurityPhoneVerifySecurityCodeFailListener;
use App\Listeners\SecurityPhone\VerifySecurityCodeSuccessListener as SecurityPhoneVerifySecurityCodeSuccessListener;

use App\Listeners\SignIn\SendPhoneSecurityCodeSuccessListener;
use App\Listeners\SignIn\SendPhoneSecurityCodeFailListener;
use App\Listeners\SignIn\SendVerificationCodeFailListener;
use App\Listeners\SignIn\SendVerificationCodeSuccessListener;
use App\Listeners\SignIn\VerifySecurityCodeFailListener;
use App\Listeners\SignIn\VerifySecurityCodeSuccessListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        RateLimiter::for('api_rate_limiter', function (Request $request) {
            return Limit::perMinute(2)->by($request->ip())->response(function () {
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

        $this->app->singleton(EventDispatcherInterface::class, function ($app) {
            return new LaravelEventDispatcher($app->make(\Illuminate\Contracts\Events\Dispatcher::class));
        });

        // Register Event Listeners
        //Security Phone


        // Event::listen(SecurityPhoneSendPhoneSecurityCodeFailedEvent::class, SecurityPhoneSendPhoneSecurityCodeFailListener::class);
        // Event::listen(SecurityPhoneSendPhoneSecurityCodeSuccessEvent::class, SecurityPhoneSendPhoneSecurityCodeSuccessListener::class);

        // Event::listen(SecurityPhoneVerifySecurityCodeFailedEvent::class, SecurityPhoneVerifySecurityCodeFailListener::class);
        // Event::listen(SecurityPhoneVerifySecurityCodeSuccessEvent::class, SecurityPhoneVerifySecurityCodeSuccessListener::class);
        


        // //sign in
        // Event::listen(SignInSuccessEvent::class, SignInSuccessListener::class);
        // Event::listen(SendPhoneSecurityCodeFailedEvent::class, SendPhoneSecurityCodeFailListener::class);
        // Event::listen(SendPhoneSecurityCodeSuccessEvent::class, SendPhoneSecurityCodeSuccessListener::class);

        // Event::listen(SendVerificationCodeFailedEvent::class, SendVerificationCodeFailListener::class);
        // Event::listen(SendVerificationCodeSuccessEvent::class, SendVerificationCodeSuccessListener::class);

        // Event::listen(VerifySecurityCodeFailedEvent::class, VerifySecurityCodeFailListener::class);
        // Event::listen(VerifySecurityCodeSuccessEvent::class, VerifySecurityCodeSuccessListener::class);

        // //other
        // Event::listen(AuthenticatedEvent::class, AuthenticatedListener::class);

    }
}
