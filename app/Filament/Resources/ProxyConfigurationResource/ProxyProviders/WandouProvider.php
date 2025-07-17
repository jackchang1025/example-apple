<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms;

class WandouProvider extends ProxyProvider
{
    public static function getKey(): string
    {
        return 'wandou';
    }

    public static function getName(): string
    {
        return 'wandou';
    }

    public static function getFields(): array
    {
        return [
            Forms\Components\Select::make('configuration.drivers.wandou.mode')
                ->options([
                    'extract_ip' => '提取模式',
                ])
                ->default('extract_ip')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.drivers.wandou.app_key')
                ->helperText('提取模式时需要 开放的app_key,可以通过用户个人中心获取'),

            Forms\Components\TextInput::make('configuration.drivers.wandou.username')
                ->helperText('账密模式时需要，可以通过用户个人中心获取'),

            Forms\Components\TextInput::make('configuration.drivers.wandou.password')
                ->helperText('账密模式时需要，可以通过用户个人中心获取'),

            Forms\Components\Select::make('configuration.drivers.wandou.xy')
                ->options([
                    1 => 'HTTP/HTTPS',
                    3 => 'SOCKS5',
                ])
                ->default(1)
                ->helperText('代理协议'),

            Forms\Components\Select::make('configuration.drivers.wandou.isp')
                ->options([
                    null => '不限',
                    1    => '电信',
                    2    => '移动',
                    3    => '联通',
                ])
                ->default(null)
                ->helperText('运营商选择'),

            Forms\Components\TextInput::make('configuration.wandou.area_id')
                ->default(0)
                ->helperText('地区id,默认0全国混播,多个地区使用|分割,查看地区 https://h.wandouip.com/help/news/432113'),

            Forms\Components\TextInput::make('configuration.drivers.wandou.num')
                ->numeric()
                ->default(1)
                ->helperText('提取模式时需要单次提取IP数量,最大100'),

            Forms\Components\Toggle::make('configuration.drivers.wandou.nr')
                ->default(false)
                ->helperText('提取模式时需要 是否自动去重'),

            Forms\Components\TextInput::make('configuration.drivers.wandou.life')
                ->numeric()
                ->default(1)
                ->helperText('提取模式时需要 尽可能保持一个ip的使用时间(分钟)'),
        ];
    }
}
