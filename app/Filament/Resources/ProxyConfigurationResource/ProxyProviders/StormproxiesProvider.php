<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms;

class StormproxiesProvider extends ProxyProvider
{
    public static function getKey(): string
    {
        return 'stormproxies';
    }

    public static function getName(): string
    {
        return 'Stormproxies';
    }

    public static function getFields(): array
    {
        return [
            Forms\Components\Select::make('configuration.drivers.stormproxies.mode')
                ->options([
                    'direct_connection_ip' => '账密模式',
                    'extract_ip' => '提取模式',
                ])
                ->default('direct_connection_ip')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.drivers.stormproxies.username')
                            ->helperText('用户名'),
            
            Forms\Components\TextInput::make('configuration.drivers.stormproxies.password')
                ->helperText('密码'),

            Forms\Components\TextInput::make('configuration.drivers.stormproxies.app_key')
                ->helperText('开放的app_key,可以通过用户个人中心获取'),

            Forms\Components\TextInput::make('configuration.drivers.stormproxies.pt')
                ->helperText('套餐id,提取界面选择套餐可指定对应套餐进行提取'),

            Forms\Components\Select::make('configuration.drivers.stormproxies.host')
                ->options([
                    'proxy.stormip.cn' => '智能',
                    'hk.stormip.cn' => '亚洲区域',
                    'us.stormip.cn' => '美洲区域',
                    'eu.stormip.cn' => '欧洲区域',
                ])
                ->default('proxy.stormip.cn')
                ->helperText('选择代理网络(代理网络是指中转服务器的位置)'),

            Forms\Components\TextInput::make('configuration.drivers.stormproxies.port')
                ->default('1000')
                ->helperText('端口'),
            
            Forms\Components\Select::make('configuration.drivers.stormproxies.protocol')
                ->options([
                    'http'   => 'HTTP/HTTPS',
                ])
                ->default('http')
                ->helperText('选择代理协议'),

            Forms\Components\TextInput::make('configuration.drivers.stormproxies.area')
                ->helperText('国家代码(ISO 3166-1 alpha-2)：示例 cn, 此代理无法同时设置多个国家/地区，留空表示随机。')
                ->default(null),

            Forms\Components\Toggle::make('configuration.drivers.stormproxies.sticky_session')
            ->label('启用粘性会话')
            ->helperText('此选项能够让您在会话期间始终保持代理不变。使用粘性会话，您可以配置“生命周期”参数，该参数决定在切换到新代理之前使用相同代理的时间。这对于需要持续连接到同一IP地址的任务特别有用，例如在访问具有基于会话的身份验证或追踪的Web资源时始终保持会话不变。')
            ->default(false),

            Forms\Components\TextInput::make('configuration.drivers.stormproxies.life')
                ->helperText('会话持续时间(分钟),仅在开启粘性会话时有效,会指示路由器会话保持有效的持续时间。')
                ->default('10'),
        ];
    }
}
