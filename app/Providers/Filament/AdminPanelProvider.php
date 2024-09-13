<?php

namespace App\Providers\Filament;

use App\Filament\Pages\LogView;
use App\Filament\Resources\PageVisitsResource\Widgets\GeolocationChart;
use App\Filament\Resources\PageVisitsResource\Widgets\OnlineUsersChart;
use App\Filament\Resources\PageVisitsResource\Widgets\TotalVisitsChart;
use App\Filament\Widgets\PageVisits;
use App\Http\Middleware\EnforceSecuritySettingsMiddleware;
use App\Models\SecuritySetting;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Resources\RoleResource;
use Filament\Forms\Components\TextInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentLaravelLog\FilamentLaravelLogPlugin;
use TomatoPHP\FilamentUsers\FilamentUsersPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        return $panel
            ->default()
            ->id('admin')
            ->path($this->getAdminPath())
            ->pages([])
            ->login()
            // 如果你想支持语言切换
            ->passwordReset()
            ->emailVerification()
            ->profile(isSimple: false)
            ->bootUsing(function (Panel $panel) {
                // ...
            })
            ->plugins([
//                \FilipFonal\FilamentLogManager\FilamentLogManager::make(),
//                FilamentLaravelLogPlugin::make(),
                FilamentShieldPlugin::make(),
                FilamentUsersPlugin::make(),
                FilamentLaravelLogPlugin::make()
                    ->navigationGroup('')
                    ->viewLog(LogView::class),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
//                Widgets\AccountWidget::class,
//                TotalVisitsChart::class,
//                OnlineUsersChart::class,
//                GeolocationChart::class,
                PageVisits::class,
//                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnforceSecuritySettingsMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->databaseNotifications();
    }

    public function getAdminPath(): string
    {
        try {
            $securitySettings = SecuritySetting::first();
            return $securitySettings?->safe_entrance ? trim(
                $securitySettings?->safe_entrance,
                '/'
            ) : 'admin';
        } catch (\Exception $e) {

            return 'admin';
        }
    }
}
