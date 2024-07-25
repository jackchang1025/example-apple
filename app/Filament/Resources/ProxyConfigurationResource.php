<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProxyConfigurationResource\Pages;
use App\Models\ProxyConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProxyConfigurationResource extends Resource
{
    protected static ?string $model = ProxyConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('给这个代理配置一个易于识别的名称'),
                Forms\Components\Select::make('configuration.default_driver')
                    ->options([
                        'flow' => 'Flow Proxy',
                        'dynamic' => 'Dynamic Proxy',
                    ])
                    ->required()
                    ->default('flow')
                    ->helperText('选择默认代理驱动'),
                Forms\Components\Tabs::make('Drivers')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Flow Proxy')
                            ->schema([
                                Forms\Components\TextInput::make('configuration.flow.orderId')
                                    ->required()
                                    ->helperText('代理订单ID'),
                                Forms\Components\TextInput::make('configuration.flow.pwd')
                                    ->required()
                                    ->password()
                                    ->helperText('代理订单密码'),
                                Forms\Components\Select::make('configuration.flow.mode')
                                    ->options([
                                        0 => '默认账密模式',
                                        1 => '通道模式',
                                    ])
                                    ->default(0)
                                    ->helperText('选择代理模式'),
                                Forms\Components\TextInput::make('configuration.flow.pid')
                                    ->default('-1')
                                    ->helperText('省份ID，-1表示随机'),
                                Forms\Components\TextInput::make('configuration.flow.cid')
                                    ->default('-1')
                                    ->helperText('城市ID，-1表示随机'),
                                Forms\Components\Toggle::make('configuration.flow.sip')
                                    ->default(1)
                                    ->helperText('是否切换IP：关闭表示自动切换，开启表示不能切换'),
                                Forms\Components\TextInput::make('configuration.flow.uid')
                                    ->default('')
                                    ->helperText('自定义UID，相同的UID会尽可能采用相同的IP'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Dynamic Proxy')
                            ->schema([
                                Forms\Components\TextInput::make('configuration.dynamic.orderId')
                                    ->required()
                                    ->helperText('代理订单ID'),
                                Forms\Components\TextInput::make('configuration.dynamic.secret')
                                    ->required()
                                    ->helperText('代理订单密钥'),
                                Forms\Components\Select::make('configuration.dynamic.type')
                                    ->options([
                                        1 => 'HTTP/HTTPS',
                                    ])
                                    ->default(1)
                                    ->helperText('选择IP协议'),
                                Forms\Components\TextInput::make('configuration.dynamic.num')
                                    ->numeric()
                                    ->default(1)
                                    ->minLength(1, 200)
                                    ->maxLength(200)
                                    ->helperText('提取数量：1-200之间'),
                                Forms\Components\TextInput::make('configuration.dynamic.pid')
                                    ->default(-1)
                                    ->helperText('省份ID：-1表示中国'),
                                Forms\Components\TextInput::make('configuration.dynamic.unbindTime')
                                    ->numeric()
                                    ->default(600)
                                    ->minValue(1)
                                    ->helperText('占用时长（单位：秒）'),
                                Forms\Components\TextInput::make('configuration.dynamic.cid')
                                    ->default('')
                                    ->helperText('城市ID，留空表示随机'),
                                Forms\Components\Toggle::make('configuration.dynamic.noDuplicate')
                                    ->default(0)
                                    ->helperText('是否去重：关闭表示不去重，开启表示24小时去重'),
                                Forms\Components\Select::make('configuration.dynamic.dataType')
                                    ->options([
                                        0 => 'JSON',
                                    ])
                                    ->default(0)
                                    ->helperText('选择返回的数据格式'),
//                                Forms\Components\TextInput::make('configuration.dynamic.lineSeparator')
//                                    ->numeric()
//                                    ->default(0)
//                                    ->helperText('行分隔符'),
                                Forms\Components\Toggle::make('configuration.dynamic.singleIp')
                                    ->default(0)
                                    ->helperText('异常切换：关闭表示切换，开启表示不切换'),
                            ]),
                    ]),
                Forms\Components\Toggle::make('is_active')
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
                    })
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
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
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
