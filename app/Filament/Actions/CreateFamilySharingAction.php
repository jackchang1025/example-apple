<?php

namespace App\Filament\Actions;

use App\Models\Account;
use App\Services\FamilyService;
use Exception;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class CreateFamilySharingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createFamilySharing';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('创建家庭共享')
            ->icon('heroicon-o-user-group')
            ->modalHeading('创建家庭共享')
            ->modalDescription('开通家庭共享需要主账号和一个拥有付款帐号，付款帐号也可以是主账号本身')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->form([


                Section::make('主账号')
                    ->description('开通家庭共享需要主账号')
                    ->schema([
                        TextInput::make('account')
                            ->default(fn(Account $account) => $account->account)
                            ->disabled(),

                        TextInput::make('password')
                            ->default(fn(Account $account) => $account->password)
                            ->disabled(),
                    ]),

                Section::make('付款帐号')
                    ->description('开通家庭共享需要付款帐号，留空则使用主账号作为付款帐号')
                    ->schema([
                        Select::make('account_id')
                            ->options(Account::all()->pluck('account', 'id'))
                            ->live()
                            ->disabled(fn($get) => !empty($get('pay_account')) && !empty($get('pay_password'))
                            )  // 当输入了账号密码时禁用
                            ->dehydrated(fn($get) => empty($get('pay_account')) || empty($get('pay_password'))
                            )  // 当输入了账号密码时不提交此字段
                            ->searchable()
                            ->label('请选择付款账号')
                            ->placeholder('请选择付款账号'),

                        TextInput::make('pay_account')
                            ->live()  // 实时更新
                            ->disabled(fn($get) => (bool)$get('account_id'))
                            ->dehydrated(fn($get) => !$get('account_id'))
                            ->label('账号')
                            ->placeholder('请输入付款账号'),

                        TextInput::make('pay_password')
                            ->live()  // 实时更新
                            ->disabled(fn($get) => (bool)$get('account_id'))
                            ->dehydrated(fn($get) => !$get('account_id'))
                            ->label('密码')
                            ->placeholder('请输入密码'),
                    ]),


            ])
            ->action(function (Account $record, array $data) {


                try {

                    $this->handleFamilySharing($record, $data);

                    Notification::make()
                        ->title('创建家庭共享组成功')
                        ->success()
                        ->send();

                } catch (Exception $e) {

                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                }

            });
    }

    protected function handleFamilySharing(Account $account, array $data): void
    {

        $payAccount = $this->getAccount($data);
        if (!empty($payAccount)) {
            $pay_account  = $payAccount['pay_account'];
            $pay_password = $payAccount['pay_password'];
        } else {
            $pay_account  = $account->account;
            $pay_password = $account->password;
        }

        //{"organizerAppleId":"licade_2015@163.com","organizerAppleIdForPurchases":"jackchang2021@163.com","organizerAppleIdForPurchasesPassword":"AtA3FH2sBfrtSv6","organizerShareMyLocationEnabledDefault":true,"iTunesTosVersion":284005}
        FamilyService::make($account)->createFamily($account, $pay_account, $pay_password);

    }

    public function getAccount(array $data): ?array
    {
        if (!empty($data['account_id'])) {

            $appleId = Account::findOrFail($data['account_id']);

            return ['pay_account' => $appleId['account'], 'pay_password' => $appleId['password']];
        }

        if (!empty($data['pay_account']) && !empty($data['pay_password'])) {
            return ['pay_account' => $data['pay_account'], 'pay_password' => $data['pay_password']];
        }

        return null;
    }
}
