<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms;

class SmartdailiProvider extends ProxyProvider
{
    public static function getKey(): string
    {
        return 'smartdaili';
    }

    public static function getName(): string
    {
        return 'Smartdaili';
    }

    public static function getFields(): array
    {
        return [
            Forms\Components\Select::make('configuration.drivers.smartdaili.mode')
                ->options([
                    'direct_connection_ip' => '账密模式',
                ])
                ->default('direct_connection_ip')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.drivers.smartdaili.username')
                ->label('用户名')
                ->helperText('Smartdaili代理用户名'),

            Forms\Components\TextInput::make('configuration.drivers.smartdaili.password')
                ->label('密码')
                ->helperText('Smartdaili代理密码'),

            Forms\Components\TextInput::make('configuration.drivers.smartdaili.host')
                ->label('代理服务器')
                ->helperText('Smartdaili代理服务器地址'),

            Forms\Components\TextInput::make('configuration.drivers.smartdaili.port')
                ->label('端口')
                ->numeric()
                ->helperText('Smartdaili代理端口'),

            Forms\Components\Select::make('configuration.drivers.smartdaili.protocol')
                ->label('代理协议')
                ->options([
                    'http'   => 'HTTP/HTTPS',
                    'socks5' => 'SOCKS5',
                ])
                ->default('http')
                ->helperText('选择代理协议类型'),

                Forms\Components\TextInput::make('configuration.drivers.smartdaili.country')
                ->helperText('国家代码(ISO 3166-1 alpha-2 format) 示例：country-dk,it,ie, 可以设置多个国家/地区，留空表示随机')
                ->default(null),

            Forms\Components\TextInput::make('configuration.drivers.smartdaili.state')
                ->helperText('州/省代码,用于针对美国的一个州。该值应该是该州的名称。一定要选择美国作为国家。留空表示随机')
                ->default(null),

            Forms\Components\TextInput::make('configuration.drivers.smartdaili.city')
                ->helperText('城市名称。将此参数添加到用户名中将允许你指定要使用的 IP 所在的城市。请将此参数与国家/地区参数一起使用。留空表示随机')
                ->default(null),
            Forms\Components\TextInput::make('configuration.drivers.smartdaili.session')
            ->label('会话ID')
            ->helperText('会话ID(用于粘性会话,如果为空则自动生成)')
            ->default(null),

            Forms\Components\Toggle::make('configuration.drivers.smartdaili.sticky_session')
                ->label('启用粘性会话')
                ->helperText('此选项能够让您在会话期间始终保持代理不变。使用粘性会话，您可以配置“生命周期”参数，该参数决定在切换到新代理之前使用相同代理的时间。这对于需要持续连接到同一IP地址的任务特别有用，例如在访问具有基于会话的身份验证或追踪的Web资源时始终保持会话不变。')
                ->default(false),
            Forms\Components\TextInput::make('configuration.drivers.smartdaili.sessionduration')
            ->helperText('会话持续时间(分钟),与会话一起使用。指定粘滞会话时间（以分钟为单位） - 可以设置为 1 到 30 之间的任意数字。如果未指定此参数，会话默认持续 10 分钟。')
            ->default(10),
        ];
    }
}
