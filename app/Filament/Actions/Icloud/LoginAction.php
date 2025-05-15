<?php

namespace App\Filament\Actions\Icloud;

use App\Models\Account;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Authenticate\Authenticate;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Weijiajia\SaloonphpAppleClient\Exception\SignInException;

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
            ->closeModalByClickingAway(false)
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
            ->successNotificationTitle('验证码已重新发送')
            ->action(function () {

                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $apple = app(AppleBuilder::class)->build($account->toAccount());

                    $apple->getWebResource()->getIdmsaResource()->signIn();

                    $this->success();

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
    public function handleAuth(account $apple, $data): Authenticate
    {

        $webAuthenticate = $apple->getWebResource()->getIdmsaResource();

        if (empty($data['authorizationCode'])) {

            $auth = $apple->appleIdResource()->appleAuth();
            if ($auth->hasTrustedDevices()) {
                throw new \RuntimeException('此账号设备在线，无法使用手机验证码授权，请使用设备验证码登录');
            }
            // 1. 获取所有授权手机过
            //滤可信的手机号码
            $trustedPhones = $auth->filterTrustedPhone();

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
     * @param Account $apple
     * @return Authenticate
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     * @throws SignInException
     */
    public function initializeLogin(Account $apple): Authenticate
    {
        $loginDelegates = $apple->getFamilyResources()->login();

        $apple->appleIdResource()->signIn();

        return $loginDelegates;
    }
}
