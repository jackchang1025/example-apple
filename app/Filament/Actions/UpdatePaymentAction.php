<?php

namespace App\Filament\Actions;

use App\Models\Account;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use RuntimeException;

class UpdatePaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'UpdatePaymentAction';
    }

    protected function setUp(): void
    {
        parent::setUp();


        $this->label('更新支付方式')
            ->icon('heroicon-o-user-group')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->action(function (Account $record, array $data) {


                try {
                    $this->handle($record, $data);

                    Notification::make()
                        ->title('更新支付方式成功')
                        ->success()
                        ->send();

                } catch (Exception $e) {

                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                }
            })->getActionFunction();
    }

    protected function handle(Account $record, array $data): void
    {

        $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);

        $account = $AppleAccountManagerFactory->create($record);

        dd($account->getDevices(), $account->paymentInfos());

        return;

        $account->refreshLoginState();

        $getFamilyDetails = $account->getFamilyDetails();

        if (!$getFamilyDetails->isMemberOfFamily) {
            throw new RuntimeException('请先创建家庭共享组');
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

    protected function showLoginModal(Account $record): void
    {
        // 初始化登录动作
        $loginAction = LoginAction::make()
            ->record($record)
            ->form([
                TextInput::make('account')
                    ->default($record->account)
                    ->required()
                    ->label('账号'),
                TextInput::make('password')
                    ->default($record->password)
                    ->required()
                    ->label('密码'),
            ])
            ->modalSubmitActionLabel('登录')
            ->modalCancelActionLabel('取消')
            ->action(function (Account $record, array $data) {
                $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);
                $account                    = $AppleAccountManagerFactory->create($record);

                try {
                    $account->login($data['account'], $data['password']);

                    Notification::make()
                        ->title('登录成功')
                        ->success()
                        ->send();

                    // 登录成功后重新尝试更新支付方式
                    $this->handle($record, $data);

                } catch (Exception $e) {
                    Notification::make()
                        ->title('登录失败：'.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });

        // 调用登录动作
        $parameters = [
            'record' => $record,
        ];

        $this->getLivewire()->mountAction('login', $parameters);
    }

}
