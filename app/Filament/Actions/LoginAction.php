<?php

namespace App\Filament\Actions;

use App\Filament\Resources\AccountResource\Pages\ListAccounts;
use App\Models\Account;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\AppleClient\Service\DataConstruct\Icloud\Authenticate\Authenticate;

class LoginAction extends Action
{

    public static function getDefaultName(): ?string
    {
        return 'login';
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->label('登陆')
            ->icon('heroicon-o-user-group')
            ->modalHeading('login')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->successNotificationTitle('登陆成功')
            ->successRedirectUrl(fn() => url('/admin/accounts'))
            ->failureRedirectUrl(fn() => url('/admin/accounts'))
            ->extraModalFooterActions([
                $this->resendDeviceCode(),
                //                $this->sendPhoneCode(),
            ])
            ->form([

                TextInput::make('account')
                    ->disabled()
                    ->label('Apple ID')
                    ->default(fn(Account $record) => $record->account),

                TextInput::make('password')
                    ->disabled()
                    ->label('password')
                    ->default(fn(Account $record) => $record->password),

                TextInput::make('bind_phone')
                    ->disabled()
                    ->label('bind_phone')
                    ->default(fn(Account $record) => $record->bind_phone),

                TextInput::make('bind_phone_address')
                    ->disabled()
                    ->label('bind_phone_address')
                    ->default(fn(Account $record) => $record->bind_phone_address)
                ,

                TextInput::make('authorizationCode')
                    ->label('授权码')
                    ->helperText('如果使用绑定手机号码登录请留空')
                    ->placeholder('请输入授权码'),
            ])
            ->beforeFormFilled(function (Account $record) {
                try {

                    //初始化登录
                    $this->initializeLogin($record);

                } catch (Exception $e) {

                    Log::error($e);

                    $this->failureNotificationTitle($e->getMessage());
                    $this->failure();
                }
            })
            ->action(function (Account $record, $data) {

                try {

                    $this->handleAuth($record, $data);

                    $this->success();

                } catch (\Exception $e) {

                    Log::error($e);

                    Notification::make()
                        ->title($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            });
    }

    public function resendDeviceCode(): Action
    {
        return Action::make('resendDeviceCode')
            ->label('发送设备验证码')
            ->color('warning')
            ->action(function (Account $record, ListAccounts $livewire) {

                try {

                    $webAuthenticate = app(AppleAccountManagerFactory::class)
                        ->create($record)
                        ->getWebAuthenticate();

                    $webAuthenticate->login();

                    $this->successNotificationTitle('验证码已重新发送')->sendSuccessNotification();

                } catch (Exception $e) {

                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();

                }
            });
    }

    public function handleAuth(account $record, $data): Authenticate
    {

        $appleAccountManager = app(AppleAccountManagerFactory::class)->create($record);

        $webAuthenticate = $appleAccountManager->getWebAuthenticate();

        if (empty($data['authorizationCode'])) {
            if ($webAuthenticate->getAuth()->hasTrustedDevices()) {
                throw new \RuntimeException('此账号设备在线，无法使用手机验证码授权，请使用设备验证码登录');
            }
            // 1. 获取所有授权手机
            $allTrustedPhones = $webAuthenticate->getTrustedPhones();

            // 2. 尝试获取验证码
            $data['authorizationCode'] = $webAuthenticate->attemptGetVerificationFromPhones($allTrustedPhones);
        }

        if (empty($data['authorizationCode'])) {
            throw new \RuntimeException('登录失败获取验证码失败');
        }

        //3 开始授权
        $authenticate = $appleAccountManager->fetchAuthenticateAuth($data['authorizationCode']);

        if (!$record->dsid && $authenticate->appleAccountInfo->dsid) {
            $record->dsid = $authenticate->appleAccountInfo->dsid;
            $record->save();
        }

        return $authenticate;
    }

    public function sendPhoneCode(): Action
    {
        return Action::make('sendPhoneCode')
            ->label('使用绑定手机号码登录')
            ->color('warning')
            ->action(function (Account $record, Action $action, ListAccounts $livewire) {

                try {

                    $appleAccountManager = app(AppleAccountManagerFactory::class)->create($record);

                    $webAuthenticate = $appleAccountManager->getWebAuthenticate();

                    if ($webAuthenticate->getAuth()->hasTrustedDevices()) {
                        throw new \RuntimeException('此账号设备在线，无法使用手机验证码授权，请使用设备验证码登录');
                    }

                    // 1. 获取所有授权手机
                    $allTrustedPhones = $webAuthenticate->getTrustedPhones();

                    // 3. 尝试获取验证码
                    $verifyPhoneSecurityCode = $webAuthenticate->attemptGetVerificationFromPhones($allTrustedPhones);

                    //开始登录
                    $appleAccountManager->fetchAuthenticateAuth($verifyPhoneSecurityCode);
//
                    Notification::make()
                        ->title('登陆成功')
                        ->success()
                        ->send();

                } catch (\Exception $e) {

                    Log::error($e);

                    Notification::make()
                        ->title($e->getMessage() ?? '未知错误')
                        ->warning()
                        ->send();
                }
            });
    }

    /**
     * @param Account $record
     * @return Authenticate
     */
    public function initializeLogin(Account $record): Authenticate
    {
        $AppleAccountManagerFactory = app(AppleAccountManagerFactory::class);
        $appleAccountManager = $AppleAccountManagerFactory->create($record);

        /**
         * @var Authenticate $loginDelegates
         */
        $loginDelegates = $appleAccountManager->fetchAuthenticateLogin();

        $webAuthenticate = $AppleAccountManagerFactory->create($record)->getWebAuthenticate();

        $webAuthenticate->login();

        return $loginDelegates;
    }
}
