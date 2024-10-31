<?php

namespace App\Filament\Resources;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers\DevicesRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\LogsRelationManager;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $label = '账号';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('account')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(AccountStatus::getDescriptionValuesArray())
                    ->required(),
                Forms\Components\TextInput::make('bind_phone')->rules(['nullable', 'phone:AUTO']),
                Forms\Components\TextInput::make('bind_phone_address')->rule(['nullable', 'url', 'max:255']),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class,
            DevicesRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('account')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('password')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn (AccountStatus $state): string => $state->description())
                    ->color(fn (AccountStatus $state): string => $state->color())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn(AccountType $state): string => $state->description())
                    ->color(fn(AccountType $state): string => $state->color())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bind_phone')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('bind_phone_address')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable()
                    ->dateTime(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([

                // 添加 QueryBuilder 用于模糊搜索
                //                QueryBuilder::make()
                //                    ->constraints([
                //                        TextConstraint::make('account')
                //                            ->label('账号')
                //                            ->icon('heroicon-m-user'),
                //
                //                        TextConstraint::make('password')
                //                            ->label('密码')
                //                            ->icon('heroicon-m-key'),
                //
                //                        TextConstraint::make('bind_phone')
                //                            ->label('绑定手机')
                //                            ->icon('heroicon-m-device-phone-mobile'),
                //
                //                        SelectConstraint::make('status')
                //                            ->options(AccountStatus::getDescriptionValuesArray())
                //                            ->label('状态'),
                //
                //                        SelectConstraint::make('type')
                //                            ->options(AccountType::getDescriptionValuesArray())
                //                            ->label('类型'),
                //                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options(AccountStatus::getDescriptionValuesArray())
                    ->label('选择状态')
                    ->placeholder('选择状态'),

                Tables\Filters\SelectFilter::make('type')
                    ->options(AccountType::getDescriptionValuesArray())
                    ->label('选择类型')
                    ->placeholder('选择类型'),


                SelectFilter::make('payment_type')
                    ->label('支付方式')
                    ->placeholder('选择类型')
                    ->options(function () {
                        return \App\Models\Payment::query()
                            ->selectRaw('COALESCE(NULLIF(TRIM(payment_method_name), ""), "无") as payment_method_name')
                            ->distinct()
                            ->pluck('payment_method_name', 'payment_method_name')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($data) {
                            foreach ($data['values'] as $value) {
                                if ($value === '无') {
                                    $query->orWhere(function ($query) {
                                        // 没有 payment 关联记录的情况
                                        $query->doesntHave('payment');
                                    })->orWhereHas('payment', function ($query) {
                                        // 有 payment 关联记录但 payment_method_name 为空的情况
                                        $query->whereNull('payment_method_name')
                                            ->orWhere('payment_method_name', '')
                                            ->orWhere('payment_method_name', '无');
                                    });
                                } else {
                                    $query->orWhereHas('payment', function ($query) use ($value) {
                                        $query->where('payment_method_name', $value);
                                    });
                                }
                            }
                        });
                    })
                    ->multiple()
                    ->searchable()
                    ->preload(),

            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('基本信息')
                    ->schema([
                        TextEntry::make('account')->label('账号'),
                        TextEntry::make('password')->label('密码'),
                        TextEntry::make('status')->label('状态')
                            ->formatStateUsing(fn (AccountStatus $state): string => $state->description())
                            ->color(fn (AccountStatus $state): string => $state->color()),

                        TextEntry::make('type')->label('类型')
                            ->formatStateUsing(fn(AccountType $state): string => $state->description())
                            ->color(fn(AccountType $state): string => $state->color()),


                        TextEntry::make('bind_phone')->label('绑定手机号码'),
                        TextEntry::make('bind_phone_address')->label('绑定手机号码地址'),
                        TextEntry::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('updated_at')->label('更新时间')->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2),

                Section::make('支付信息')
                    ->schema([

                        TextEntry::make('payment.payment_method_name')->label('支付方式名称'),
                        TextEntry::make('payment.payment_method_detail')->label('支付方式详情'),
                        TextEntry::make('payment.partner_login')->label('合作伙伴登录信息'),
                        TextEntry::make('payment.payment_account_country_code')->label('支付账户国家代码'),
                        TextEntry::make('payment.type')->label('支付方式类型'),

                        IconEntry::make('payment.is_primary')
                            ->label('是否主要支付方式')
                            ->icon(fn(bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'
                            )
                            ->color(fn(bool $state): string => $state ? 'success' : 'danger'),


                        TextEntry::make('payment.is_primary')
                            ->label('是否主要支付方式')
                            ->formatStateUsing(fn(bool $state): string => $state ? '是' : '否'),

                        TextEntry::make('payment.we_chat_pay')
                            ->label('是否微信支付')
                            ->formatStateUsing(fn(bool $state): string => $state ? '是' : '否'),

                        TextEntry::make('payment.payment_supported')
                            ->label('是否支持支付')
                            ->formatStateUsing(fn(bool $state): string => $state ? '是' : '否'),

                        TextEntry::make('payment.family_card')
                            ->label('是否家庭卡')
                            ->formatStateUsing(fn(bool $state): string => $state ? '是' : '否'),

                        TextEntry::make('payment.expiration_supported')
                            ->label('是否支持过期')
                            ->formatStateUsing(fn(bool $state): string => $state ? '是' : '否'),

                        //
                        //                        TextEntry::make('payment.is_primary')->label('是否主要支付方式'),
                        //                        TextEntry::make('payment.we_chat_pay')->label('是否微信支付'),
                        //                        TextEntry::make('payment.payment_supported')->label('是否支持支付'),
                        //                        TextEntry::make('payment.family_card')->label('是否家庭卡'),
                        //                        TextEntry::make('payment.expiration_supported')->label('是否支持过期'),

                        Fieldset::make('电话信息')
                            ->schema([
                                TextEntry::make('payment.phone_number.number')->label('号码'),
                                TextEntry::make('payment.phone_number.countryCode')->label('国家代码'),
                            ]),

                        Fieldset::make('所有者信息')
                            ->schema([
                                TextEntry::make('payment.owner_name.firstName')->label('名'),
                                TextEntry::make('payment.owner_name.lastName')->label('姓'),
                            ]),

                        Fieldset::make('账单地址')
                            ->schema([
                                TextEntry::make('payment.billing_address.line1')->label('地址行1'),
                                TextEntry::make('payment.billing_address.line2')->label('地址行2'),
                                TextEntry::make('payment.billing_address.city')->label('城市'),
                                TextEntry::make('payment.billing_address.stateProvince')->label('州/省'),
                                TextEntry::make('payment.billing_address.postalCode')->label('邮编'),
                                TextEntry::make('payment.billing_address.countryName')->label('国家'),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
            'view' => Pages\ViewAccount::route('/{record}'),
        ];
    }
}
