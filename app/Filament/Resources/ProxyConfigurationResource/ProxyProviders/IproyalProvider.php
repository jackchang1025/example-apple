<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms;

class IproyalProvider extends ProxyProvider
{
    public static function getKey(): string
    {
        return 'iproyal';
    }

    public static function getName(): string
    {
        return 'IPRoyal';
    }

    public static function getFields(): array
    {
        return [
            Forms\Components\Select::make('configuration.drivers.iproyal.mode')
                ->options([
                    'direct_connection_ip'    => '账密模式',
                ])
                ->default('direct_connection_ip')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.username')
                ->label('用户名')
                ->helperText('住宅代理用户名')
                ->dehydrated(true),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.password')
                ->label('密码')
                ->helperText('住宅代理密码')
                ->dehydrated(true),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.host')
                ->label('代理服务器')
                ->default('geo.iproyal.com')
                ->helperText('住宅代理服务器地址'),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.port')
                ->label('端口')
                ->default('12321')
                ->helperText('住宅代理端口,请记住，端口将根据协议（HTTP/SOCKS5）而有所不同。'),

            Forms\Components\Select::make('configuration.drivers.iproyal.protocol')
                ->options([
                    'http'   => 'HTTP/HTTPS',
                    'socks5' => 'SOCKS5',
                ])
                ->default('http')
                ->helperText('选择代理协议,在配置代理时，重要的是要考虑最适合您需求的协议。有两种主要类型可用：HTTP/HTTPS和SOCKS5。每种协议都在其不同的端口上运行，并服务于不同的目的。我们提供两种常见的代理协议类型。'),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.country')
                ->helperText('国家代码(ISO 3166-1 alpha-2 format) 示例：dk,it,ie,可以选择多个国家。留空表示随机')
                ->default(null),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.state')
                ->helperText('州/省代码,用于针对美国的一个州。该值应该是该州的名称。一定要选择美国作为国家。留空表示随机')
                ->default(null),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.region')
                ->helperText('区域代码,是配置区域的关键。添加此值可告诉我们的路由器筛选出位于该区域的代理,留空表示随机')
                ->default(null),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.city')
                ->helperText('城市代码,是锁定城市的关键。该值应该是城市的名称。此外，在针对特定城市时，必须指定国家，因为多个国家可能有同名的城市。留空表示随机')
                ->default(null),

            Forms\Components\Toggle::make('configuration.drivers.iproyal.sticky_session')
                ->label('启用粘性会话')
                ->helperText('此选项能够让您在会话期间始终保持代理不变。使用粘性会话，您可以配置“生命周期”参数，该参数决定在切换到新代理之前使用相同代理的时间。这对于需要持续连接到同一IP地址的任务特别有用，例如在访问具有基于会话的身份验证或追踪的Web资源时始终保持会话不变。')
                ->default(false),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.session')
            ->label('会话ID')
            ->helperText('会话ID(用于粘性会话,如果为空则自动生成)')
            ->default(null),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.lifetime')
                ->helperText('会话持续时间(m:分钟,h:小时,d:天),仅在开启粘性会话时有效,会指示路由器会话保持有效的持续时间。最短持续时间设置为1秒，最长持续时间设置为7天。注意这里的格式至关重要：只能指定一个时间单位。这个参数对于定义粘性会话的操作跨度、平衡会话稳定性和安全需求方面都起到非常关键的作用。')
                ->default('10m'),

            Forms\Components\Toggle::make('configuration.drivers.iproyal.streaming')
                ->label('启用高端池')
                ->helperText('激活后，“高端池”选项能够让您访问我们甄选出的最快速且最可靠的代理。但请注意，在提升品质的同时，可用代理的池会比通常可访问的总池要小，也就是“贵精不贵多”的道理。')
                ->default(false),

            Forms\Components\Toggle::make('configuration.drivers.iproyal.skipispstatic')
                ->label('跳过静态ISP')
                ->helperText('启用后，此选项可让我们的路由器跳过静态代理')
                ->default(false),

            Forms\Components\Toggle::make('configuration.drivers.iproyal.forcerandom')
                ->label('强制随机')
                ->helperText('强制随机IP')
                ->default(true),

            Forms\Components\TextInput::make('configuration.drivers.iproyal.skipipslist')
                ->helperText('跳过IP功能够让您生成多个IP范围列表，这些列表将在代理连接的IP解析过程中被自动绕过。要启用此功能，您需要添加 _skipipslist- 键，该键的值是生成列表的ULID (id)')
                ->default(null),
        ];
    }
}
