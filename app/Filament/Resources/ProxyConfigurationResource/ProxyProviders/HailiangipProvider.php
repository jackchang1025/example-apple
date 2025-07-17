<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms;

class HailiangipProvider extends ProxyProvider
{
    public static function getKey(): string
    {
        return 'hailiangip';
    }

    public static function getName(): string
    {
        return 'Hailiangip';
    }

    public static function getFields(): array
    {
        return [
            Forms\Components\Select::make('configuration.drivers.hailiangip.mode')
                ->options([
                    'direct_connection_ip' => '默认账密模式',
                    'extract_ip' => '提取模式',
                ])
                ->default('direct_connection_ip')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.drivers.hailiangip.orderId')
                ->helperText('代理订单ID'),
            Forms\Components\TextInput::make('configuration.drivers.hailiangip.pwd')
                ->helperText('代理订单密码'),

            Forms\Components\TextInput::make('configuration.drivers.hailiangip.secret')
                ->helperText('代理订单密钥'),

            Forms\Components\TextInput::make('configuration.drivers.hailiangip.pid')
                ->default(-1)
                ->helperText('省份ID：-1表示中国'),

            Forms\Components\TextInput::make('configuration.drivers.hailiangip.cid')
                ->default('')
                ->helperText('城市ID，留空表示随机'),
            Forms\Components\Toggle::make('configuration.drivers.hailiangip.noDuplicate')
                ->default(0)
                ->helperText('是否去重：关闭表示不去重，开启表示24小时去重'),
        ];
    }
}
