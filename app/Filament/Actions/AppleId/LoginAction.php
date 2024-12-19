<?php

namespace App\Filament\Actions\AppleId;

use App\Filament\Resources\AccountResource\Pages\ListAccounts;
use App\Models\Account;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Token\Token;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

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

                    $apple = app(AppleBuilder::class)->build($record->toAccount());

                    $apple->getWebResource()->getAppleIdResource()->sendVerificationCode();

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
    public function initializeLogin(Account $record): SignInComplete
    {
        $apple = app(AppleBuilder::class)->build($record->toAccount());

        return $apple->getWebResource()->getAppleIdResource()->signIn();
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
    public function handleAuth(account $record, $data): Token
    {
        $apple = app(AppleBuilder::class)->build($record->toAccount());

        if (!empty($data['authorizationCode'])) {

            $apple->getWebResource()->getAppleIdResource()->verifySecurityCode($data['authorizationCode']);
        } else {

            $apple->getWebResource()->getAppleIdResource()->twoFactorAuthentication();
        }

        return $apple->getWebResource()->getAppleIdResource()->getAccountManagerResource()->token();
    }
}
