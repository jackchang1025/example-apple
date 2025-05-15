<?php

namespace App\Filament\Actions\AppleId;

use App\Models\Account;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class LoginAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'apple_id_login';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('登陆')
            ->icon('heroicon-o-user-group')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->successNotificationTitle('登陆成功')
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
            ->beforeFormFilled(function () {
                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    //初始化登录
                    $this->initializeLogin($account);

                } catch (Exception $e) {

                    Log::error($e);

                    $this->failureNotificationTitle($e->getMessage());
                    $this->failure();
                }
            })
            ->action(function (array $data) {

                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $this->handleAuth($account, $data);

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
            ->action(function () {

                try {

                    /**
                     * @var Account $account
                     */
                    $apple = $this->getRecord();

                    $apple->appleIdResource()->sendVerificationCode();

                    $this->successNotificationTitle('验证码已重新发送')->sendSuccessNotification();

                } catch (Exception $e) {

                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();

                }
            });
    }

    /**
     * @param Account $record
     * @return SignInComplete
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function initializeLogin(Account $apple): SignInComplete
    {
        return $apple->appleIdResource()->signIn();
    }

    /**
     * @param Account $record
     * @param $data
     * @return Token
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws FatalRequestException
     * @throws MaxRetryAttemptsException
     * @throws PhoneAddressException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws \JsonException
     * @throws \Throwable
     */
    public function handleAuth(account $apple, $data): Token
    {

        if (!empty($data['authorizationCode'])) {

            $apple->appleIdResource()->verifySecurityCode($data['authorizationCode']);
        } else {

            $apple->appleIdResource()->twoFactorAuthentication();
        }

        return $apple->appleIdResource()->getAccountManagerResource()->token();
    }
}
