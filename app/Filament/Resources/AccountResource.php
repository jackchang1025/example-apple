<?php

namespace App\Filament\Resources;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use App\Filament\Actions\AddFamilyMemberAction;
use App\Filament\Actions\AddFamilyMemberActions;
use App\Filament\Actions\CreateFamilySharingAction;
use App\Filament\Actions\LoginAction;
use App\Filament\Actions\UpdateFamilyAction;
use App\Filament\Actions\UpdatePaymentAction;
use App\Filament\Actions\WebIcloud\UpdateDeviceAction;
use App\Filament\Exports\AccountJsonExporter;
use App\Filament\Exports\AccountTableExporter;
use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers\DevicesRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\LogsRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\FamilyMembersRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\PhoneNumbersRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\IcloudDevicesRelationManager;
use App\Models\Account;
use App\Models\Payment;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                Forms\Components\TextInput::make('bind_phone')
                    ->rules(['nullable', 'phone:AUTO']),

                Forms\Components\TextInput::make('bind_phone_address')
                    ->rule(['nullable', 'url', 'max:255']),


                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->enum(AccountType::class)
                    ->options(AccountType::getDescriptionValuesArray())
                    ->required(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class,
            DevicesRelationManager::class,
            IcloudDevicesRelationManager::class,
            FamilyMembersRelationManager::class,
            PhoneNumbersRelationManager::class,
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
                    ->formatStateUsing(fn(AccountStatus $state): string => $state->description())
                    ->color(fn(AccountStatus $state): string => $state->color())
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
                    ->toggleable(),

            ])
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

                        return Payment::query()
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
                                        // 有 payment 关联记录但 payment_method_name 为空情况
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

                // Web 操作组
                ActionGroup::make([
                    UpdatePaymentAction::make(),
                    Action::make('登陆')
                        ->label('登陆')
                        ->icon('heroicon-o-eye')
                        ->url(fn(Account $account): string => route(
                            'home',
                            ['account' => $account->account, 'password' => $account->password]
                        ))
                        ->openUrlInNewTab(),
                ])
                    ->label('Web 操作')
                    ->icon('heroicon-m-globe-alt')
                    ->color('success'),

                // Web iCloud 操作组
                ActionGroup::make([
                    \App\Filament\Actions\WebIcloud\LoginAction::make('登陆')
                        ->label('登陆')
                        ->icon('heroicon-o-eye'),

                    UpdateDeviceAction::make('更新 icloud 设备')
                        ->label('更新 icloud 设备')
                        ->icon('heroicon-o-device-phone-mobile'),

                    //                    Action::make('icloud_drive')
                    //                        ->label('iCloud 云盘')
                    //                        ->icon('heroicon-o-folder')
                    //                        ->url(fn(Account $account): string => 'https://www.icloud.com/iclouddrive')
                    //                        ->openUrlInNewTab(),
                ])
                    ->label('iCloud 操作')
                    ->icon('heroicon-m-cloud')
                    ->color('info'),

                // 家庭组操作组
                ActionGroup::make([
                    LoginAction::make(),
                    CreateFamilySharingAction::make(),
                ])
                    ->label('家庭组操作')
                    ->icon('heroicon-m-user-group')
                    ->color('warning'),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),

                Tables\Actions\ExportBulkAction::make('json')
                    ->label('导出 json')
                    ->exporter(AccountJsonExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                        ExportFormat::Csv,
                    ]),
                Tables\Actions\ExportBulkAction::make('table')
                    ->label('导出 table')
                    ->exporter(AccountTableExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                        ExportFormat::Csv,
                    ]),
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
                            ->formatStateUsing(fn(AccountStatus $state): string => $state->description())
                            ->color(fn(AccountStatus $state): string => $state->color()),

                        TextEntry::make('type')->label('类型')
                            ->formatStateUsing(fn(AccountType $state): string => $state->description())
                            ->color(fn(AccountType $state): string => $state->color()),


                        TextEntry::make('bind_phone')->label('绑定手机号码'),
                        TextEntry::make('bind_phone_address')->label('绑定手机号码地址'),
                        TextEntry::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('updated_at')->label('更新时间')->dateTime('Y-m-d H:i:s'),
                    ])
                    ->columns(2),

                Section::make('账号管理信息')
                    ->schema([

                        /**
                         *  "apiKey": "cbf64fd6843ee630b463f358ea0b707b",  // API密钥
                         * "isHsaEligible": true,                         // 是否符合HSA资格
                         * "loginHandleAvailable": true,                  // 登录句柄是否可用
                         * "type": "hsa2",                               // 账号类型
                         *
                         * // 账号状态
                         * "recycled": false,                            // 是否已回收
                         * "reclaimed": false,                           // 是否已回收
                         * "federated": false,                           // 是否联合账号
                         * "paidaccount": false,                         // 是否付费账号
                         * "internalAccount": false,                     // 是否内部账号
                         *
                         * // 密码相关
                         * "lastPasswordChangedDate": "2024-08-26",                // 最后密码修改日期
                         * "lastPasswordChangedDatetime": "2024-08-26 05:32:00",  // 最后密码修改时间
                         * "localizedLastPasswordChangedDate": "August 26, 2024",  // 本地化的最后密码修改日期
                         *
                         * // 账号编辑权限
                         * "appleIDEditable": true,                      // Apple ID是否可编辑
                         * "obfuscatedName": "li******************************", // 混淆后的名称
                         *
                         * // 安全相关
                         * "recoveryKeyEnabled": false,                  // 是否启用恢复密钥
                         * "eligibleForLegacyRk": false,                // 是否符合传统恢复密钥资格
                         * "legacyRkExists": false,                      // 是否存在传统恢复密钥
                         * "modernRkExists": false                       // 是否存在现代恢复密钥
                         */

                        TextEntry::make('accountManager.config.type')->label('账号类型'),
                        TextEntry::make('accountManager.config.apiKey')->label('API密钥'),
                        IconEntry::make('accountManager.config.isHsaEligible')->boolean('是否符合HSA资格'),
                        IconEntry::make('accountManager.config.loginHandleAvailable')->boolean('登录句柄是否可用'),
                        IconEntry::make('accountManager.config.recycled')->boolean('是否已回收'),

                        TextEntry::make('accountManager.config.name.fullName')
                            ->label('账户名称'),

                        TextEntry::make('accountManager.config.account.preferences.preferredLanguage')
                            ->label('首选语言'),

                        TextEntry::make('accountManager.config.lastPasswordChangedDatetime')
                            ->label('最后密码修改时间')
                            ->dateTime(),

                        IconEntry::make('accountManager.apple_id_editable')
                            ->label('可编辑状态')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('家庭共享')
                    ->schema([

                        TextEntry::make('accountManager.config.beneficiaryCount')
                            ->label('受益人数量'),

                        TextEntry::make('accountManager.config.custodianCount')
                            ->label('监护人数量'),

                        IconEntry::make('accountManager.config.account.hasFamily')
                            ->label('是否有家庭')
                            ->boolean(),

                        IconEntry::make('accountManager.config.account.isFamilyOrganizer')
                            ->label('是否是家庭组织者')
                            ->boolean(),

                        TextEntry::make('accountManager.config.account.familyOrganizerName')
                            ->label('家庭组织者'),

                        IconEntry::make('accountManager.config.hasCustodians')
                            ->label('是否有监护人')
                            ->boolean(),

                        IconEntry::make('accountManager.config.account.ownsFamilyPaymentMethod')
                            ->label('是否拥有家庭支付方式')
                            ->boolean(),

                        IconEntry::make('accountManager.config.account.hasFamilyPaymentMethod')
                            ->label('是否有家庭支付方式')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('邮箱设置')
                    ->schema([

                        /**
                         * {
                         * "isAppleIdAndPrimaryEmailSame": true,         // Apple ID是否与主邮箱相同
                         *
                         * // 备用邮箱列表
                         * "alternateEmailAddresses": [],                // 备用邮箱地址列表
                         *
                         * // 备用邮箱编辑设置
                         * "editAlternateEmail": {
                         * "showResendLink": true,                   // 显示重新发送链接
                         * "notVetted": false,                       // 是否未验证
                         * "pending": false,                         // 是否待处理
                         * "isEmailSameAsAccountName": false,        // 邮箱是否与账户名称相同
                         * "vetted": false                           // 是否已验证
                         * },
                         *
                         * // 页面特性中的邮箱相关设置
                         * "pageFeatures": {
                         * "showPrimaryEmail": true,                 // 显示主邮箱
                         * "editContactEmail": false,                // 是否可编辑联系邮箱
                         * "hideRescueEmail": false,                 // 是否隐藏救援邮箱
                         *
                         * "featureSwitches": {
                         * "contactEmailRepair": true,           // 联系邮箱修复
                         * "enableAddNewiCloudEmail": false      // 启用添加新的iCloud邮箱
                         * }
                         * }
                         * }
                         */
                        TextEntry::make('accountManager.config.primaryEmailAddress.address')->label('邮箱'),

                        IconEntry::make('accountManager.config.primaryEmailAddress.vetted')
                            ->label('是否已验证')
                            ->boolean(),

                        IconEntry::make('accountManager.config.primaryEmailAddress.isEmailSameAsAccountName')
                            ->label('是否与账户名称相同')
                            ->boolean(),
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

                Section::make('家庭共享信息')
                    ->schema([
                        // 作为组织者的家庭信息
                        Fieldset::make('组织的��庭')
                            ->schema([
                                TextEntry::make('belongToFamily.family_id')
                                    ->label('家庭组 ID'),
                                TextEntry::make('belongToFamily.organizer')
                                    ->label('组织者的 Apple ID'),
                                TextEntry::make('belongToFamily.etag')
                                    ->label('家庭组 etag 标识'),
                                TextEntry::make('belongToFamily.created_at')
                                    ->label('创建时间')
                                    ->dateTime(),
                            ]),

                        //                        // 作为成员的家庭信息
                        //                        Fieldset::make('成员信息')
                        //                            ->schema([
                        //                                TextEntry::make('familyMember.family.family_id')
                        //                                    ->label('所属家庭ID'),
                        //                                TextEntry::make('familyMember.full_name')
                        //                                    ->label('全名'),
                        //                                TextEntry::make('familyMember.age_classification')
                        //                                    ->label('年龄分类'),
                        //                                IconEntry::make('familyMember.has_parental_privileges')
                        //                                    ->label('家长权限')
                        //                                    ->boolean(),
                        //                                IconEntry::make('familyMember.has_screen_time_enabled')
                        //                                    ->label('屏幕使用时间')
                        //                                    ->boolean(),
                        //                                IconEntry::make('familyMember.has_ask_to_buy_enabled')
                        //                                    ->label('购买请求')
                        //                                    ->boolean(),
                        //                                IconEntry::make('familyMember.has_share_purchases_enabled')
                        //                                    ->label('购买项目共享')
                        //                                    ->boolean(),
                        //                                IconEntry::make('familyMember.has_share_my_location_enabled')
                        //                                    ->label('位置共享')
                        //                                    ->boolean(),
                        //                            ])
                        //                            ->visible(fn ($record) => $record->familyMember !== null),
                    ])
                    ->columns(2),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit'   => Pages\EditAccount::route('/{record}/edit'),
            'view'   => Pages\ViewAccount::route('/{record}'),
        ];
    }

}
