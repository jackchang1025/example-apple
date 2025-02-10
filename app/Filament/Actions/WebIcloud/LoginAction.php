<?php

namespace App\Filament\Actions\WebIcloud;

use App\Models\Account;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Request\AccountLogin\AccountLogin;
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
            ->beforeFormFilled(function () {
                try {

                    /**
                     * @var Account $record
                     */
                    $record = $this->getRecord();

                    //初始化登录
                    $this->initializeLogin($record);

                } catch (Exception $e) {

                    Log::error($e);

                    $this->failureNotificationTitle($e->getMessage());
                    $this->failure();
                }
            })
            ->action(function (array $data) {


                try {

                    /**
                     * @var Account $record
                     */
                    $record = $this->getRecord();

                    $this->handleAuth($record, $data);

                    $this->success();

                } catch (\Exception $e) {

                    Log::error($e);

                    $this->failureNotificationTitle($e->getMessage());
                    $this->failure();
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
                     * @var Account $record
                     */
                    $record = $this->getRecord();

                    $apple = app(AppleBuilder::class)->build(
                        \Modules\AppleClient\Service\DataConstruct\Account::from($record->toAccount())
                    );

                    $apple->getWebResource()->getIcloudResource()->sendVerificationCode();

                    $this->successNotificationTitle('验证码已重新发送')->sendSuccessNotification();

                } catch (Exception $e) {

                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage())->sendFailureNotification();

                }
            });
    }

    /**
     * @param Account $record
     * @param array $data
     * @return \Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\AccountLogin\AccountLogin
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
    public function handleAuth(
        Account $record,
        array $data
    ): \Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\AccountLogin\AccountLogin {

        $apple = app(AppleBuilder::class)->build($record->toAccount());

        if (empty($apple->getAccount()->getDsid())) {
            throw new \RuntimeException('dsid is empty');
        }

        if (!empty($data['authorizationCode'])) {

            $apple->getWebResource()->getIcloudResource()->verifySecurityCode($data['authorizationCode']);
        } else {

            $apple->getWebResource()->getIcloudResource()->twoFactorAuthentication();
        }

        $headerRepositories = $apple->getWebResource()
            ->getIcloudResource()
            ->getHeaderSynchronize()
            ->getHeaderRepositories()
            ->all();

        if (empty($headerRepositories['X-Apple-Session-Token'])) {
            throw new \RuntimeException('No X-Apple-Session-Token cookie found');
        }

        $data = new AccountLogin(
            dsWebAuthToken: $headerRepositories['X-Apple-Session-Token'],
            accountCountryCode: $apple->getAccount()->getAccountCountryCode(),
            extended_login: false,
            dsid: $apple->getAccount()->getDsid()
        );

        return $apple->getWebResource()->getIcloudResource()->getAuthenticateResources()->accountLogin($data);
    }

    /**
     * @param Account $record
     * @return \Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\AccountLogin\AccountLogin
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function initializeLogin(Account $record
    ): \Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\AccountLogin\AccountLogin {
        $apple = app(AppleBuilder::class)->build(
            \Modules\AppleClient\Service\DataConstruct\Account::from($record->toAccount())
        );

        $apple->getWebResource()->getIcloudResource()->signIn();

        $headerRepositories = $apple->getWebResource()->getIcloudResource()->getHeaderSynchronize(
        )->getHeaderRepositories()->all();

        if (empty($headerRepositories['X-Apple-Session-Token'])) {
            throw new \RuntimeException('No X-Apple-Session-Token cookie found');
        }

        $data = new AccountLogin(
            dsWebAuthToken: $headerRepositories['X-Apple-Session-Token'],
            accountCountryCode: $apple->getAccount()->getAccountCountryCode(),
            extended_login: false
        );

        $accountInfo = $apple->getWebResource()->getIcloudResource()->getAuthenticateResources()->accountLogin($data);

        if (!$record->dsid && $accountInfo->dsInfo->dsid) {
            $record->dsid = $accountInfo->dsInfo->dsid;
            $record->save();
        }

        return $accountInfo;
    }
}
