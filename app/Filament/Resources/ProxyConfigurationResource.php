<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProxyConfigurationResource\Pages;
use App\Models\ProxyConfiguration;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ProxyConfigurationResource extends Resource
{
    protected static ?string $model = ProxyConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $label = '代理设置';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Split::make([
                    Section::make('Main Content')
                        ->schema([
                            Forms\Components\Tabs::make('Driver Configuration')
                                ->tabs([
                                    Forms\Components\Tabs\Tab::make('Hailiangip')
                                        ->schema(self::getHailiangipSchema()),

                                    Forms\Components\Tabs\Tab::make('Stormproxies')
                                        ->schema(self::getStormproxiesSchema()),

                                    Forms\Components\Tabs\Tab::make('Huashengdaili')
                                        ->schema(self::getHuashengdaili()),

                                    Forms\Components\Tabs\Tab::make('Wandou')
                                        ->schema(self::getWandouSchema()),

                                    Forms\Components\Tabs\Tab::make('IPRoyal')
                                        ->schema(self::getIproyalSchema()),

                                    Forms\Components\Tabs\Tab::make('Smartdaili')
                                        ->schema(self::getSmartdailiSchema()),
                                ]),
                        ])
                        ->columnSpan(['lg' => 3]),
                    Section::make('Meta Information')
                        ->schema([

                            Forms\Components\TextInput::make('name')
                                ->label('代理配置名称')
                                ->required()
                                ->maxLength(255)
                                ->helperText('给这个代理配置一个易于识别的名称'),

                            Forms\Components\Select::make('configuration.default_driver')
                                ->label('代理驱动')
                                ->options([
                                    'hailiangip' => 'Hailiangip',
                                    'stormproxies' => 'Stormproxies',
                                    'huashengdaili' => 'Huashengdaili',
                                    'wandou'        => '豌豆代理',
                                    'iproyal'       => 'IPRoyal',
                                    'smartdaili'    => 'Smartdaili',
                                ])
                                ->required()
                                ->default('stormproxies')
                                ->helperText('选择默认代理驱动')
                                ->reactive(),

                            Forms\Components\Toggle::make('proxy_enabled')
                                ->label('是否开启代理')
                                ->required()
                                ->default(ProxyConfiguration::OFF)
                                ->helperText('开启则使用代理，关闭则不使用代理'),

                            Forms\Components\Toggle::make('ipaddress_enabled')
                                ->label('根据用户 IP 地址自动选择代理')
                                ->required()
                                ->default(ProxyConfiguration::OFF)
                                ->helperText('开启后将根据用户 IP 地址选择代理 IP的地址，关闭则使用随机的代理 IP 地址,注意暂时只支持国内 IP 地址'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('是否开启默认代理')
                                ->required()
                                ->default(false)
                                ->helperText('激活此配置将使其成为默认代理配置，并会自动取消激活其他配置。')
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    if ($state) {
                                        // 如果用户正在激活这个配置，我们不需要做任何事情
                                        // 模型的 boot 方法会处理取消激活其他配置
                                    } else {
                                        // 如果用户正在取消激活这个配置，我们需要确保至少有一个配置是活动的
                                        $activeConfigs = ProxyConfiguration::where('is_active', true)->count();
                                        if ($activeConfigs === 0) {
                                            $set('is_active', true);
                                            Notification::make()
                                                ->warning()
                                                ->title('无法取消激活')
                                                ->body('必须至少有一个活动的代理配置。')
                                                ->send();
                                        }
                                    }
                                }),

                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                    ->from('md')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
//                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('configuration.default_driver')
                    ->label('Default Driver')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
//                Tables\Filters\SelectFilter::make('default_driver')
//                    ->options([
//                        'flow' => 'Flow Proxy',
//                        'dynamic' => 'Dynamic Proxy',
//                    ])
//                    ->attribute('configuration->default_driver'),
//                Tables\Filters\TernaryFilter::make('is_active')
//                    ->label('Active Status')
//                    ->trueLabel('Active')
//                    ->falseLabel('Inactive')
//                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Action::make('activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('dedault config')
                    ->modalDescription('激活此配置将使其成为默认代理配置，并会自动取消激活其他配置。')
                    ->visible(fn (ProxyConfiguration $record): bool => !$record->is_active)
                    ->action(function (ProxyConfiguration $record) {
                        $record->update(['is_active' => true]);
                        Notification::make()
                            ->success()
                            ->title('配置已激活')
                            ->body('该配置现在是默认的代理配置。')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getHailiangipSchema(): array{
        return [
            Forms\Components\Select::make('configuration.hailiangip.mode')
                ->options([
                    'flow' => '默认账密模式',
                    'dynamic' => '通道模式',
                ])
                ->default('flow')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.hailiangip.orderId')
//                ->required()
                ->helperText('代理订单ID'),
            Forms\Components\TextInput::make('configuration.hailiangip.pwd')
//                ->required()
                ->password()
                ->helperText('代理订单密码'),

            Forms\Components\TextInput::make('configuration.hailiangip.secret')
//                ->required()
                ->helperText('代理订单密钥'),

//            Forms\Components\TextInput::make('configuration.hailiangip.pid')
//                ->default('-1')
//                ->helperText('省份ID，-1表示随机'),
//            Forms\Components\TextInput::make('configuration.hailiangip.cid')
//
//                ->default('-1')
//                ->helperText('城市ID，-1表示随机'),
//            Forms\Components\Toggle::make('configuration.hailiangip.sip')
//                ->default(1)
//                ->helperText('是否切换IP：关闭表示自动切换，开启表示不能切换'),

//            Forms\Components\TextInput::make('configuration.hailiangip.uid')
//                ->default('')
//                ->helperText('自定义UID，相同的UID会尽可能采用相同的IP'),

//            Forms\Components\Select::make('configuration.hailiangip.type')
//                ->options([
//                    1 => 'HTTP/HTTPS',
//                ])
//                ->default(1)
//                ->helperText('选择IP协议'),
//            Forms\Components\TextInput::make('configuration.hailiangip.num')
//                ->numeric()
//                ->default(1)
//                ->minLength(1, 200)
//                ->maxLength(200)
////                ->helperText('提取数量：1-200之间'),
            Forms\Components\TextInput::make('configuration.hailiangip.pid')
                ->default(-1)
                ->helperText('省份ID：-1表示中国'),
//            Forms\Components\TextInput::make('configuration.hailiangip.unbindTime')
//                ->numeric()
//                ->default(600)
//                ->minValue(1)
//                ->helperText('占用时长（单位：秒）'),
            Forms\Components\TextInput::make('configuration.hailiangip.cid')
                ->default('')
                ->helperText('城市ID，留空表示随机'),
            Forms\Components\Toggle::make('configuration.hailiangip.noDuplicate')
                ->default(0)
                ->helperText('是否去重：关闭表示不去重，开启表示24小时去重'),
//            Forms\Components\Select::make('configuration.hailiangip.dataType')
//                ->options([
//                    0 => 'JSON',
//                ])
//                ->default(0)
//                ->helperText('选择返回的数据格式'),
//            Forms\Components\Toggle::make('configuration.hailiangip.singleIp')
//                ->default(0)
//                ->helperText('异常切换：关闭表示切换，开启表示不切换'),
        ];
    }

    protected static function getStormproxiesSchema(): array{
        return [
            Forms\Components\Select::make('configuration.stormproxies.mode')
                ->options([
                    'flow' => '账密模式',
                    'dynamic' => '通道模式',
                ])
//                ->required()
                ->default('flow')
                ->helperText('选择代理模式'),

            Forms\Components\Select::make('configuration.stormproxies.host')
                ->options([
                    'proxy.stormip.cn' => '智能',
                    'hk.stormip.cn' => '亚洲区域',
                    'us.stormip.cn' => '美洲区域',
                    'eu.stormip.cn' => '欧洲区域',
                ])
//                ->required()
                ->default('proxy.stormip.cn')
                ->helperText('选择代理网络(代理网络是指中转服务器的位置)'),

            Forms\Components\Select::make('configuration.stormproxies.area')
                ->options([
                    '' => '全球混播',
                    'hk' => '香港',
                    'us' => '美国',
                    'cn' => '中国',
                ])
                ->default('cn')
                ->helperText('选择节点国家'),

            Forms\Components\TextInput::make('configuration.stormproxies.username')
//                ->required()
                ->helperText('用户名'),

            Forms\Components\TextInput::make('configuration.stormproxies.password')
//                ->required()
                ->helperText('密码'),

            Forms\Components\TextInput::make('configuration.stormproxies.app_key')
//                ->required()
                ->helperText('开放的app_key,可以通过用户个人中心获取'),

            Forms\Components\TextInput::make('configuration.stormproxies.pt')
                ->helperText('套餐id,提取界面选择套餐可指定对应套餐进行提取'),
        ];
    }

    public static function getHuashengdaili():array
    {
        return [

            Forms\Components\Select::make('configuration.huashengdaili.mode')
                ->options([
                    'api' => 'api 提取',
                ])
                ->default('api')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.huashengdaili.session')
                ->helperText('session密钥'),

            Forms\Components\Toggle::make('configuration.huashengdaili.only')
                ->default(false)
                ->helperText('是否去重'),

            //                    Forms\Components\TextInput::make('configuration.huashengdaili.province')
            //                        ->numeric()
            //                        ->helperText('省份编号'),
            //
            //                    Forms\Components\TextInput::make('configuration.huashengdaili.city')
            //                        ->numeric()
            //                        ->helperText('城市编号'),

            Forms\Components\Select::make('configuration.huashengdaili.iptype')
                ->options([
                    'tunnel' => '隧道',
                    'direct' => '直连',
                ])
                ->default('direct')
                ->helperText('IP类型'),
            //                    Forms\Components\Select::make('configuration.huashengdaili.pw')
            //                        ->options([
            //                            'yes' => '是',
            //                            'no' => '否',
            //                        ])
            //                        ->required()
            //                        ->default('no')
            //                        ->helperText('是否需要账号密码'),

            //                    Forms\Components\Select::make('configuration.huashengdaili.protocol')
            //                        ->options([
            //                            'http' => 'HTTP/HTTPS',
            //                            's5' => 'SOCKS5',
            //                        ])
            //                        ->required()
            //                        ->default('http')
            //                        ->helperText('IP协议'),
            //
            //                    Forms\Components\Select::make('configuration.huashengdaili.separator')
            //                        ->options([
            //                            1 => '回车换行(\r\n)',
            //                            2 => '回车(\r)',
            //                            3 => '换行(\n)',
            //                            4 => 'Tab(\t)',
            //                            5 => '空格( )',
            //                        ])
            //                        ->required()
            //                        ->default(1)
            //                        ->helperText('分隔符样式'),
            //                    Forms\Components\Select::make('configuration.huashengdaili.format')
            //                        ->options([
            //                            'null' => '不需要返回城市和IP过期时间',
            //                            'city' => '返回城市省份',
            //                            'time' => '返回IP过期时间',
            //                            'city,time' => '返回城市和IP过期时间',
            //                        ])
            //                        ->required()
            //                        ->default('city,time')
            //                        ->helperText('其他返还信息'),

        ];
    }

    // 新增豌豆代理配置schema
    protected static function getWandouSchema(): array
    {
        return [
            Forms\Components\Select::make('configuration.wandou.mode')
                ->options([
                    'flow'    => '账密模式',
                    'dynamic' => '通道模式',
                ])
                ->default('flow')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.wandou.app_key')
                ->helperText('通道模式时需要 开放的app_key,可以通过用户个人中心获取'),

            //            Forms\Components\TextInput::make('configuration.wandou.session')
            //                ->helperText('账密模式时需要 session 值'),

            Forms\Components\TextInput::make('configuration.wandou.username')
                ->helperText('账密模式时需要，可以通过用户个人中心获取'),

            Forms\Components\TextInput::make('configuration.wandou.password')
                ->helperText('账密模式时需要，可以通过用户个人中心获取'),

            Forms\Components\TextInput::make('configuration.wandou.host')
                ->default('api.wandoujia.com')
                ->helperText('账密模式时需要，可以通过用户个人中心获取'),

            Forms\Components\TextInput::make('configuration.wandou.port')
                ->default('1000')
                ->helperText(' 账密模式时需要，可以通过用户个人中心获取'),

            Forms\Components\Select::make('configuration.wandou.xy')
                ->options([
                    1 => 'HTTP/HTTPS',
                    3 => 'SOCKS5',
                ])
                ->default(1)
                ->helperText('代理协议'),

            Forms\Components\Select::make('configuration.wandou.isp')
                ->options([
                    null => '不限',
                    1    => '电信',
                    2    => '移动',
                    3    => '联通',
                ])
                ->default(null)
                ->helperText('运营商选择'),

            //            Forms\Components\TextInput::make('configuration.wandou.area_id')
            //                ->default(0)
            //                ->helperText('地区id,默认0全国混播,多个地区使用|分割'),

            Forms\Components\TextInput::make('configuration.wandou.num')
                ->numeric()
                ->default(1)
                ->helperText('通道模式时需要单次提取IP数量,最大100'),

            Forms\Components\Toggle::make('configuration.wandou.nr')
                ->default(false)
                ->helperText('通道模式时需要 是否自动去重'),

            Forms\Components\TextInput::make('configuration.wandou.life')
                ->numeric()
                ->default(1)
                ->helperText('通道模式时需要 尽可能保持一个ip的使用时间(分钟)'),

            //            Forms\Components\TextInput::make('configuration.wandou.pid')
            //                ->helperText('省份id'),
            //
            //            Forms\Components\TextInput::make('configuration.wandou.cid')
            //                ->helperText('城市id'),
        ];
    }

    protected static function getIproyalSchema(): array
    {
        return [
            Forms\Components\Select::make('configuration.iproyal.proxy_type')
                ->options([
                    'residential' => '住宅代理',
                    'datacenter'  => '数据中心代理',
                    'mobile'      => '移动代理',
                ])
                ->default('residential')
                ->reactive()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    // 更新当前激活的代理类型
                    $set('configuration.iproyal.active_type', $state);
                })
                ->helperText('选择代理类型'),

            // 住宅代理配置
            Forms\Components\Section::make('住宅代理配置')
                ->schema([
                    Forms\Components\TextInput::make('configuration.iproyal.residential.username')
                        ->label('用户名')
                        ->required()
                        ->helperText('住宅代理用户名')
                        ->dehydrated(true), // 确保数据被保存

                    Forms\Components\TextInput::make('configuration.iproyal.residential.password')
                        ->label('密码')
                        ->password()
                        ->required()
                        ->helperText('住宅代理密码')
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('configuration.iproyal.residential.endpoint')
                        ->label('代理服务器')
                        ->default('geo.iproyal.com')
                        ->required()
                        ->helperText('住宅代理服务器地址'),

                    Forms\Components\TextInput::make('configuration.iproyal.residential.port')
                        ->label('端口')
                        ->default('12321')
                        ->required()
                        ->helperText('住宅代理端口'),

                    Forms\Components\Select::make('configuration.iproyal.residential.protocol')
                        ->options([
                            'http'   => 'HTTP/HTTPS',
                            'socks5' => 'SOCKS5',
                        ])
                        ->default('http')
                        ->helperText('选择代理协议'),

                    Forms\Components\TextInput::make('configuration.iproyal.residential.country')
                        ->helperText('国家代码,如:us,cn等,留空表示随机')
                        ->default(''),

                    Forms\Components\TextInput::make('configuration.iproyal.residential.state')
                        ->helperText('州/省代码,留空表示随机')
                        ->default(''),

                    Forms\Components\TextInput::make('configuration.iproyal.residential.region')
                        ->helperText('区域代码,留空表示随机')
                        ->default(''),

                    Forms\Components\Toggle::make('configuration.iproyal.residential.sticky_session')
                        ->label('启用粘性会话')
                        ->helperText('开启后将尽可能使用相同的IP')
                        ->default(false),

                    Forms\Components\TextInput::make('configuration.iproyal.residential.session_duration')
                        ->helperText('会话持续时间(分钟),仅在开启粘性会话时有效')
                        ->numeric()
                        ->default(10)
                        ->visible(fn(Forms\Get $get) => $get('configuration.iproyal.residential.sticky_session')),

                    Forms\Components\Toggle::make('configuration.iproyal.residential.streaming')
                        ->label('启用高端池')
                        ->helperText('启用高端IP池')
                        ->default(false),

                    Forms\Components\Toggle::make('configuration.iproyal.residential.skip_isp_static')
                        ->label('跳过静态ISP')
                        ->helperText('启用跳过静态ISP功能')
                        ->default(false),

                    Forms\Components\TextInput::make('configuration.iproyal.residential.skip_ips_list')
                        ->helperText('跳过IP列表ID')
                        ->default(''),
                ])
                ->visible(fn(Forms\Get $get) => $get('configuration.iproyal.proxy_type') === 'residential')
                ->dehydrated(true), // 确保整个部分都被保存

            // 数据中心代理配置
            Forms\Components\Section::make('数据中心代理配置')
                ->schema([
                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.username')
                        ->label('用户名')
                        ->required()
                        ->helperText('数据中心代理用户名')
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.password')
                        ->label('密码')
                        ->password()
                        ->required()
                        ->helperText('数据中心代理密码')
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.endpoint')
                        ->label('代理服务器')
                        ->default('dc.iproyal.com')
                        ->required()
                        ->helperText('数据中心代理服务器地址'),

                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.port')
                        ->label('端口')
                        ->default('12321')
                        ->required()
                        ->helperText('数据中心代理端口'),

                    Forms\Components\Select::make('configuration.iproyal.datacenter.protocol')
                        ->options([
                            'http'   => 'HTTP/HTTPS',
                            'socks5' => 'SOCKS5',
                        ])
                        ->default('http')
                        ->helperText('选择代理协议'),

                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.country')
                        ->helperText('国家代码,如:us,cn等,留空表示随机')
                        ->default(''),

                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.state')
                        ->helperText('州/省代码,留空表示随机')
                        ->default(''),

                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.region')
                        ->helperText('区域代码,留空表示随机')
                        ->default(''),

                    Forms\Components\Toggle::make('configuration.iproyal.datacenter.sticky_session')
                        ->label('启用粘性会话')
                        ->helperText('开启后将尽可能使用相同的IP')
                        ->default(false),

                    Forms\Components\TextInput::make('configuration.iproyal.datacenter.session_duration')
                        ->helperText('会话持续时间(分钟),仅在开启粘性会话时有效')
                        ->numeric()
                        ->default(10)
                        ->visible(fn(Forms\Get $get) => $get('configuration.iproyal.datacenter.sticky_session')),
                ])
                ->visible(fn(Forms\Get $get) => $get('configuration.iproyal.proxy_type') === 'datacenter')
                ->dehydrated(true),

            // 移动代理配置
            Forms\Components\Section::make('移动代理配置')
                ->schema([
                    Forms\Components\TextInput::make('configuration.iproyal.mobile.username')
                        ->label('用户名')
                        ->required()
                        ->helperText('移动代理用户名')
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('configuration.iproyal.mobile.password')
                        ->label('密码')
                        ->password()
                        ->required()
                        ->helperText('移动代理密码')
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('configuration.iproyal.mobile.endpoint')
                        ->label('代理服务器')
                        ->default('mobile.iproyal.com')
                        ->required()
                        ->helperText('移动代理服务器地址'),

                    Forms\Components\TextInput::make('configuration.iproyal.mobile.port')
                        ->label('端口')
                        ->default('12321')
                        ->required()
                        ->helperText('移动代理端口'),

                    Forms\Components\Select::make('configuration.iproyal.mobile.protocol')
                        ->options([
                            'http'   => 'HTTP/HTTPS',
                            'socks5' => 'SOCKS5',
                        ])
                        ->default('http')
                        ->helperText('选择代理协议'),

                    Forms\Components\TextInput::make('configuration.iproyal.mobile.country')
                        ->helperText('国家代码,如:us,cn等,留空表示随机')
                        ->default(''),

                    Forms\Components\TextInput::make('configuration.iproyal.mobile.state')
                        ->helperText('州/省代码,留空表示随机')
                        ->default(''),

                    Forms\Components\TextInput::make('configuration.iproyal.mobile.region')
                        ->helperText('区域代码,留空表示随机')
                        ->default(''),

                    Forms\Components\Toggle::make('configuration.iproyal.mobile.sticky_session')
                        ->label('启用粘性会话')
                        ->helperText('开启后将尽可能使用相同的IP')
                        ->default(false),

                    Forms\Components\TextInput::make('configuration.iproyal.mobile.session_duration')
                        ->helperText('会话持续时间(分钟),仅在开启粘性会话时有效')
                        ->numeric()
                        ->default(10)
                        ->visible(fn(Forms\Get $get) => $get('configuration.iproyal.mobile.sticky_session')),
                ])
                ->visible(fn(Forms\Get $get) => $get('configuration.iproyal.proxy_type') === 'mobile')
                ->dehydrated(true),
        ];
    }

    protected static function getSmartdailiSchema(): array
    {
        return [

            Forms\Components\Select::make('configuration.smartdaili.mode')
                ->options([
                    'flow' => '账密模式',
                ])
                ->required()
                ->default('flow')
                ->helperText('选择代理模式'),

            Forms\Components\TextInput::make('configuration.smartdaili.username')
                ->label('用户名')
                ->required()
                ->helperText('Smartdaili代理用户名'),

            Forms\Components\TextInput::make('configuration.smartdaili.password')
                ->label('密码')
                ->password()
                ->required()
                ->helperText('Smartdaili代理密码'),

            Forms\Components\TextInput::make('configuration.smartdaili.endpoint')
                ->label('代理服务器')
                ->required()
                ->helperText('Smartdaili代理服务器地址'),

            Forms\Components\TextInput::make('configuration.smartdaili.port')
                ->label('端口')
                ->required()
                ->numeric()
                ->helperText('Smartdaili代理端口'),

            Forms\Components\Select::make('configuration.smartdaili.protocol')
                ->label('代理协议')
                ->options([
                    'http'   => 'HTTP/HTTPS',
                    'socks5' => 'SOCKS5',
                ])
                ->default('http')
                ->required()
                ->helperText('选择代理协议类型'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProxyConfigurations::route('/'),
            'create' => Pages\CreateProxyConfiguration::route('/create'),
            'edit' => Pages\EditProxyConfiguration::route('/{record}/edit'),
        ];
    }
}
