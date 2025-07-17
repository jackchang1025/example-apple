<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms;

class HuashengdailiProvider extends ProxyProvider
{
    public static function getKey(): string
    {
        return 'huashengdaili';
    }

    public static function getName(): string
    {
        return 'Huashengdaili';
    }

    public static function getFields(): array
    {
        return [
            Forms\Components\Select::make('configuration.drivers.huashengdaili.mode')
                ->options([
                    'api' => 'api 提取',
                ])
                ->default('api')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.drivers.huashengdaili.session')
                ->helperText('session密钥'),

            Forms\Components\Toggle::make('configuration.drivers.huashengdaili.only')
                ->default(false)
                ->helperText('是否去重'),

            Forms\Components\Select::make('configuration.drivers.huashengdaili.iptype')
                ->options([
                    'tunnel' => '隧道',
                    'direct' => '直连',
                ])
                ->default('direct')
                ->helperText('IP类型'),
        ];
    }
}
