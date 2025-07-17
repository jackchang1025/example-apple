<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProxyConfigurationResource\Pages;
use App\Filament\Resources\ProxyConfigurationResource\ProxyProviders\HailiangipProvider;
use App\Filament\Resources\ProxyConfigurationResource\ProxyProviders\HuashengdailiProvider;
use App\Filament\Resources\ProxyConfigurationResource\ProxyProviders\IproyalProvider;
use App\Filament\Resources\ProxyConfigurationResource\ProxyProviders\SmartdailiProvider;
use App\Filament\Resources\ProxyConfigurationResource\ProxyProviders\SmartproxyProvider;
use App\Filament\Resources\ProxyConfigurationResource\ProxyProviders\StormproxiesProvider;
use App\Filament\Resources\ProxyConfigurationResource\ProxyProviders\WandouProvider;
use App\Models\ProxyConfiguration;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class ProxyConfigurationResource extends Resource
{
    protected static ?string $model = ProxyConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $label = '代理设置';

    protected static ?string $modelLabel = '代理设置';

    protected static ?string $pluralModelLabel = '代理设置';


    protected static array $proxyProviders = [
        HailiangipProvider::class,
        StormproxiesProvider::class,
        HuashengdailiProvider::class,
        WandouProvider::class,
        IproyalProvider::class,
        SmartdailiProvider::class,
        SmartproxyProvider::class,
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(self::getFormSchema());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\EditProxyConfiguration::route('/'),
        ];
    }

    /**
     * @return array<\Filament\Forms\Components\Component>
     */
    protected static function getFormSchema(): array
    {
        return [
            Split::make([
                Section::make('代理驱动配置')
                    ->schema([
                        Forms\Components\Tabs::make('Driver Configuration')
                            ->tabs(self::getProxyDriverTabs()),
                    ])
                    ->columnSpan(['lg' => 3]),
                Section::make('通用设置')
                    ->schema(self::getGeneralSettings()),
            ])
                ->from('md')
                ->columnSpanFull()
                ,
        ];
    }

    /**
     * @return array<\Filament\Forms\Components\Tabs\Tab>
     */
    protected static function getProxyDriverTabs(): array
    {
        return collect(self::$proxyProviders)
            ->map(fn (string $provider) => 
                Forms\Components\Tabs\Tab::make($provider::getName())
                    ->schema($provider::getFields())
            )
            ->toArray();
    }

    /**
     * @return array<\Filament\Forms\Components\Component>
     */
    protected static function getGeneralSettings(): array
    {
        return [
            Forms\Components\Select::make('configuration.default')
                ->label('代理驱动')
                ->options(
                    collect(self::$proxyProviders)
                        ->mapWithKeys(fn (string $provider) => [$provider::getKey() => $provider::getName()])
                        ->toArray()
                )
                ->required()
                ->default('stormproxies')
                ->helperText('选择默认代理驱动')
                ->reactive(),

            Forms\Components\Toggle::make('configuration.proxy_enabled')
                ->label('是否开启代理')
                ->required()
                ->default(false)
                ->helperText('开启则使用代理，关闭则不使用代理'),

            Forms\Components\Toggle::make('configuration.ipaddress_enabled')
                ->label('根据用户 IP 地址自动选择代理')
                ->required()
                ->default(false)
                ->helperText('开启后将根据用户 IP 地址选择代理 IP的地址，关闭则使用随机的代理 IP 地址,注意暂时只支持国内 IP 地址'),
        ];
    }
}