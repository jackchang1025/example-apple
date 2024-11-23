<?php

namespace App\Filament\Actions;

use App\Models\Account;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Modules\AppleClient\Service\AppleAccountManagerFactory;

class AddFamilyMemberAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'addFamilyMember';
    }

    protected function setUp(): void
    {
        parent::setUp();


        $this->label('添加家庭共享成员')
            ->icon('heroicon-o-user-group')
            ->modalDescription('创建家庭共享需要绑定支付方式')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->form([

                Select::make('account_id')
                    ->options(Account::all()->pluck('account', 'id'))
                    ->rules([
                        function ($get) {
                            if (!empty($get('account')) && !empty($get('password'))) {
                                return [];  // 如果已输入账号密码，则不需要选择账号
                            }

                            return ['required'];  // 否则必选
                        },
                    ])
                    ->live()
                    ->disabled(fn($get) => !empty($get('account')) && !empty($get('password')))  // 当输入了账号密码时禁用
                    ->dehydrated(fn($get) => empty($get('account')) || empty($get('password')))  // 当输入了账号密码时不提交此字段
                    ->helperText(
                        fn($get) => !empty($get('account')) && !empty(
                        $get(
                            'password'
                        )
                        ) ? '已输入账号密码，无需选择' : '当账号和密码都为空时必选'
                    )
                    ->searchable()
                    ->label('选择账号')
                    ->placeholder('请选择账号'),

                TextInput::make('account')
                    ->rules([
                        function ($get) {
                            if ($get('account_id')) {
                                return [];  // 如果选择了account_id，则无需验证
                            }

                            return ['required'];  // 否则必填
                        },
                    ])
                    ->live()  // 实时更新
                    ->disabled(fn($get) => (bool)$get('account_id'))
                    ->dehydrated(fn($get) => !$get('account_id'))
                    ->label('账号')
                    ->placeholder('请输入账号'),

                TextInput::make('password')
                    ->rules([
                        function ($get) {
                            if ($get('account_id')) {
                                return [];
                            }

                            return ['required'];
                        },
                    ])
                    ->live()  // 实时更新
                    ->disabled(fn($get) => (bool)$get('account_id'))
                    ->dehydrated(fn($get) => !$get('account_id'))
                    ->label('密码')
                    ->placeholder('请输入密码'),


                Select::make('card')->options(function (Account $record) {
                    $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);

                    $account = $AppleAccountManagerFactory->create($record);

                    if (!$account->isLoginValid()) {
                        throw new \RuntimeException('登录信息失效，请重新登录');
                    }

                    $account->getITunesAccountPaymentInfoRequest();

                    $payment = $account->getPayment();

                }),

                TextInput::make('cvv')
                    ->required()
                    ->rules('size:3')
                    ->label('cvv')
                    ->placeholder('请输入 cvv 密码'),


            ])
            ->action(function (Account $record, array $data) {


                try {
                    $this->handle($record, $data);

                    Notification::make()
                        ->title('添加家庭共享组成功')
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

    protected function handle(Account $record, array $data): void
    {

        $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);

        $account = $AppleAccountManagerFactory->create($record);

        if (!$account->isLoginValid()) {
            throw new \RuntimeException('登录信息失效，请重新登录');
        }

        $account->refreshLoginState();

        $getFamilyDetails = $account->getFamilyDetails();

        if (!$getFamilyDetails->isMemberOfFamily) {
            throw new \RuntimeException('请先创建家庭共享组');
        }

        if (!empty($data['account_id'])) {

            $appleId          = Account::findOrFail($data['account_id']);
            $data['account']  = $appleId->account;
            $data['password'] = $appleId->password;
        }

        $familyInfo = $account->addFamilyMember($data['cvv'], $data['account'], $data['password']);

        // Update family information
        $family = $familyInfo->updateOrCreate();

        // update family members information
        $familyInfo->updateOrCreateFamilyMembers($family->family_id);

    }
}
