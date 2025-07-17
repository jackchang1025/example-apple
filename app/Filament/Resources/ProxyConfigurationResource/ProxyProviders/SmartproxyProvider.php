<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms;

class SmartproxyProvider extends ProxyProvider
{
    public static function getKey(): string
    {
        return 'smartproxy';
    }

    public static function getName(): string
    {
        return 'SmartProxy';
    }

    public static function getFields(): array
    {
        return [
            Forms\Components\Select::make('configuration.drivers.smartproxy.mode')
                ->options([
                    'direct_connection_ip' => '账密模式',
                ])
                ->default('direct_connection_ip')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.username')
                ->label('选择套餐账号')
                ->helperText('选择套餐账号'),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.password')
                ->label('密码')
                ->helperText('SmartProxy 代理密码'),

                Forms\Components\Select::make('configuration.drivers.smartproxy.host')
                ->options([
                    'proxy.smartproxycn.com' => '智能',
                    'as.smartproxycn.com' => '亚洲区域',
                    'eu.smartproxycn.com' => '美洲区域',
                    'us.smartproxycn.com' => '欧洲区域',
                ])
                ->default('proxy.stormip.cn')
                ->helperText('选择代理网络(代理网络是指中转服务器的位置)'),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.port')
                ->label('端口')
                ->numeric()
                ->helperText('SmartProxy代理端口'),

            Forms\Components\Select::make('configuration.drivers.smartproxy.protocol')
                ->label('代理协议')
                ->options([
                    'http'   => 'HTTP/HTTPS',
                    'socks5' => 'SOCKS5',
                ])
                ->default('http')
                ->helperText('选择代理协议类型'),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.area')
            ->helperText('国家代码(ISO 3166-1 alpha-2 format) 示例：us, 此代理无法设置多个国家/地区，留空表示随机')
            ->default(''),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.city')
                ->helperText('州/省代码,留空表示随机')
                ->default(''),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.state')
                ->helperText('区域代码,留空表示随机')
                ->default(''),

            Forms\Components\Toggle::make('configuration.drivers.smartproxy.sticky_session')
                ->label('启用粘性会话')
                ->helperText('开启后将尽可能使用相同的IP')
                ->default(false),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.life')
                ->helperText('尽可能保持一个ip的使用时间(分钟),仅在开启粘性会话时有效')
                ->numeric()
                ->default(10),

            Forms\Components\TextInput::make('configuration.drivers.smartproxy.ip')
            ->helperText('指定数据中心地址'),
        ];
    }
}
