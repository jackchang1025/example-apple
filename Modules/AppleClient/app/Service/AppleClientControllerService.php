<?php

namespace Modules\AppleClient\Service;


use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Jobs\BindAccountPhone;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Modules\AppleClient\Service\DataConstruct\Auth\Auth;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendDeviceSecurityCode;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\DataConstruct\Sign\Sign;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

class AppleClientControllerService
{
    protected AppleAccountManager $accountManager;

    public function __construct(
        protected readonly AppleAccountManagerFactory $accountManagerFactory,
        protected readonly Request $request,
    )
    {

    }

    public function getAccount(): Account
    {
        return $this->getAccountManager()->getAccount();
    }

    public function withAccountManager(AppleAccountManager $accountManager): static
    {
        $this->accountManager = $accountManager;

        return $this;
    }

    public function getAccountManager(): AppleAccountManager
    {
        return $this->accountManager ??= $this->accountManagerFactory->create($this->getGuidByRequest());
    }

    public function getGuidByRequest()
    {
        return $this->request->cookie('Guid', $this->request->input("Guid"));
    }

    public function getGuid(): string
    {
        return $this->getAccountManager()->getAccount()->getSessionId();
    }

    protected function dispatchBindAccountPhone(): void
    {
        BindAccountPhone::dispatch($this->getAccountManager()->getAccount())
            ->delay(Carbon::now()->addSeconds(5));

    }

    /**
     * @return Sign
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function sign(): DataConstruct\Sign\Sign
    {
        $response = $this->getAccountManager()->refreshSign();

        $this->getAccountManager()->getAccount()->save();

        Event::dispatch(
            new AccountLoginSuccessEvent(account: $this->getAccountManager()->getAccount(), description: "登录成功")
        );

        return $response;
    }

    /**
     * @return Auth
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function signAuth(): Auth
    {
        $sign = $this->sign();

        return $this->auth();
    }

    /**
     * @return Auth
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function auth(): Auth
    {
        return $this->getAccountManager()->auth();
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws RequestException|\JsonException
     */
    public function getTrustedPhoneNumbers(): DataCollection
    {
        return $this->getAccountManager()->auth()->getTrustedPhoneNumbers();
    }

    /**
     * @return PhoneNumber
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function getTrustedPhoneNumber(): DataConstruct\PhoneNumber
    {
        return $this->getAccountManager()->auth()->getTrustedPhoneNumber();
    }

    /**
     * @param int $id
     * @return SendPhoneVerificationCode
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function sendSms(int $id): DataConstruct\SendVerificationCode\SendPhoneVerificationCode
    {
        try {

            $response = $this->getAccountManager()->sendPhoneSecurityCode($id);

            $this->getAccountManager()->getAccount()->logs()
                ->create(['action' => '发送手机号码', 'description' => '发送手机号码成功']);

            return $response;

        } catch (\JsonException|Exception\VerificationCodeSentTooManyTimesException|FatalRequestException|RequestException $e) {

            $this->getAccountManager()->getAccount()->logs()
                ->create(['action' => '发送手机号码', 'description' => "发送手机号码失败:{$e->getMessage()}"]);

            throw $e;
        }
    }

    /**
     * @return SendDeviceSecurityCode
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function sendSecurityCode(): SendDeviceSecurityCode
    {
        try {

            $response = $this->getAccountManager()->sendSecurityCode();

            $this->getAccountManager()->getAccount()->logs()
                ->create(['action' => '发送设备码', 'description' => '发送设备码成功']);

            return $response;

        } catch (\JsonException|FatalRequestException|RequestException $e) {

            $this->getAccountManager()->getAccount()->logs()
                ->create(['action' => '发送设备码', 'description' => "发送设备码失败:{$e->getMessage()}"]);
            throw $e;
        }
    }

    /**
     * @param string $id
     * @param string $code
     * @return bool|SecurityVerifyPhone
     * @throws Exception\StolenDeviceProtectionException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws \JsonException
     * @throws FatalRequestException
     */
    public function verifyPhoneCode(string $id, string $code): bool|SecurityVerifyPhone
    {
        try {

            $response = $this->getAccountManager()->verifyPhoneCodeAndValidateStolenDeviceProtection($id, $code);

            Event::dispatch(
                new AccountAuthSuccessEvent(
                    account: $this->getAccountManager()->getAccount(),
                    description: "手机验证码: {$code} 验证成功"
                )
            );

        } catch (RequestException $e) {

            Event::dispatch(
                new AccountAuthFailEvent(
                    account: $this->getAccountManager()->getAccount(),
                    description: "手机验证码: {$code} 验证失败: {$e->getMessage()}"
                )
            );
            throw $e;
        }

        $this->dispatchBindAccountPhone();

        return $response;
    }

    /**
     * @param string $code
     * @return bool|SecurityVerifyPhone
     * @throws Exception\StolenDeviceProtectionException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws \JsonException
     * @throws FatalRequestException
     */
    public function verifySecurityCode(string $code): bool|SecurityVerifyPhone
    {

        try {
            $response = $this->getAccountManager()->verifySecurityCodeAndValidateStolenDeviceProtection($code);

            Event::dispatch(
                new AccountAuthSuccessEvent(
                    account: $this->getAccountManager()->getAccount(),
                    description: "安全码: {$code} 验证成功"
                )
            );

        } catch (RequestException $e) {

            Event::dispatch(
                new AccountAuthFailEvent(
                    account: $this->getAccountManager()->getAccount(),
                    description: "安全码: {$code} 验证失败: {$e->getMessage()}"
                )
            );
            throw $e;
        }

        $this->dispatchBindAccountPhone();

        return $response;
    }
}

