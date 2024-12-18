<?php

namespace App\Filament\Actions;

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
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Authenticate\Authenticate;
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

                    $apple->getWebResource()->getIdmsaResource()->signIn();

                    $this->successNotificationTitle('验证码已重新发送')->sendSuccessNotification();

                } catch (Exception $e) {

                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();

                }
            });
    }

    /**
     * @param Account $record
     * @param $data
     * @return Authenticate
     * @throws FatalRequestException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws \Modules\AppleClient\Service\Exception\AccountException
     * @throws \Modules\AppleClient\Service\Exception\MaxRetryAttemptsException
     * @throws \Throwable
     */
    public function handleAuth(account $record, $data): Authenticate
    {

        $apple = app(AppleBuilder::class)->build($record->toAccount());

        $webAuthenticate = $apple->getWebResource()->getIdmsaResource();

        $auth = $webAuthenticate->getAuth();

        if (empty($data['authorizationCode'])) {
            if ($auth->hasTrustedDevices()) {
                throw new \RuntimeException('此账号设备在线，无法使用手机验证码授权，请使用设备验证码登录');
            }
            // 1. 获取所有授权手机
            $trustedPhones = $webAuthenticate->filterTrustedPhone();

            if ($trustedPhones->count() === 0) {
                throw new PhoneNotFoundException("未找到可信手机号");
            }

            // 2. 尝试获取验证码
            $data['authorizationCode'] = $webAuthenticate->getTrustedPhoneCode($trustedPhones);
        }

        if (empty($data['authorizationCode'])) {
            throw new \RuntimeException('登录失败获取验证码失败');
        }

        $apiAuthenticate = $apple->getApiResources()->getIcloudResource()->getAuthenticationResource();

        //3 开始授权
        $authenticate = $apiAuthenticate->fetchAuthenticateAuth($data['authorizationCode']);

        if (!$record->dsid && $authenticate->appleAccountInfo->dsid) {
            $record->dsid = $authenticate->appleAccountInfo->dsid;
            $record->save();
        }

        return $authenticate;
    }


    /**
     * @param Account $record
     * @return Authenticate
     * @throws FatalRequestException
     * @throws RequestException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws \JsonException
     */
    public function initializeLogin(Account $record): Authenticate
    {
        $apple = app(AppleBuilder::class)->build($record->toAccount());

        $loginDelegates = $apple->getApiResources()->getIcloudResource()->getAuthenticationResource(
        )->fetchAuthenticateLogin();

        $apple->getWebResource()->getIdmsaResource()->signIn();

        return $loginDelegates;
    }
}
