<?php

namespace Modules\AppleClient\Service;

use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginFailEvent;
use App\Events\AccountLoginSuccessEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\DataConstruct\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Trait\HasNotification;
use Modules\AppleClient\Service\Trait\HasTries;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

class ProcessAccountImportService
{
    use HasNotification;
    use HasTries;


    public function __construct(protected AppleAccountManager $accountManager)
    {
        $this->tries                 = 5;
        $this->retryInterval         = 5;
        $this->useExponentialBackoff = true;
    }

    /**
     * @return void
     * @throws AccountException
     * @throws AttemptBindPhoneCodeException
     * @throws FatalRequestException
     * @throws MaxRetryAttemptsException
     * @throws PhoneAddressException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws \JsonException
     */
    public function handle(): void
    {

        $this->sign();

        $this->auth();

        // 获取安全设备
        $this->fetchDevices();

        // 获取支付方式
        $this->fetchPaymentConfig();

    }

    /**
     * @return void
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    protected function sign(): void
    {

        try {

            $this->accountManager->sign();

            Event::dispatch(
                new AccountLoginSuccessEvent(account: $this->accountManager->getAccount(), description: "登录成功")
            );

            $this->errorNotification("登录成功", "{$this->accountManager->getAccount()->account} 登录成功");

        } catch (\JsonException|FatalRequestException|RequestException $e) {

            Event::dispatch(
                new AccountLoginFailEvent(account: $this->accountManager->getAccount(), description: $e->getMessage())
            );

            $this->errorNotification("登录失败", "{$this->accountManager->getAccount()->account} {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * @return void
     * @throws AccountException
     * @throws AttemptBindPhoneCodeException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws FatalRequestException
     * @throws PhoneAddressException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws \JsonException|MaxRetryAttemptsException
     */
    protected function auth(): void
    {

        try {

            $this->validatePhoneBinding();

            $auth = $this->accountManager->auth();

            $phone = $this->findTrustedPhone($auth->getTrustedPhoneNumbers());
            if (!$phone) {
                throw new PhoneNotFoundException("未找到该账号绑定的手机号码", [
                    'account'       => $this->accountManager->getAccount()->toArray(),
                    'trusted_phone' => $auth->getTrustedPhoneNumbers()->toArray(),
                ]);
            }

            $this->attemptVerifyPhoneCode($phone);

            $this->accountManager->token();

            Event::dispatch(
                new AccountAuthSuccessEvent(account: $this->accountManager->getAccount(), description: "授权成功")
            );

            $this->errorNotification("授权成功", "{$this->accountManager->getAccount()->account} 授权成功");

        } catch (\JsonException|Exception\VerificationCodeException|AttemptBindPhoneCodeException|FatalRequestException|RequestException|MaxRetryAttemptsException $e) {

            Event::dispatch(
                new AccountAuthFailEvent(account: $this->accountManager->getAccount(), description: $e->getMessage())
            );

            $this->errorNotification("授权失败", "{$this->accountManager->getAccount()->account} {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * @param PhoneNumber $phone
     * @return VerifyPhoneSecurityCode
     * @throws FatalRequestException
     * @throws MaxRetryAttemptsException
     * @throws RequestException
     * @throws \JsonException
     */
    protected function attemptVerifyPhoneCode(PhoneNumber $phone): VerifyPhoneSecurityCode
    {

        for ($attempts = 0; $attempts < $this->getTries(); $attempts++) {

            try {

                $this->sendPhoneSecurityCode($phone);

                //为了防止拿到上一次验证码导致错误，这里建议睡眠一段时间再尝试
                usleep($this->getSleepTime($attempts, $this->getRetryInterval(), true));

                return $this->handlePhoneVerification($phone);

            } catch (VerificationCodeException|AttemptBindPhoneCodeException $e) {

                Event::dispatch(
                    new AccountAuthFailEvent(
                        account: $this->accountManager->getAccount(), description: $e->getMessage()
                    )
                );

                $this->errorNotification(
                    "授权失败",
                    "{$this->accountManager->getAccount()->account} {$e->getMessage()}"
                );

            }
        }

        throw new MaxRetryAttemptsException("最大尝试次数:{$this->tries}");
    }

    /**
     * @return void
     * @throws AccountException
     * @throws PhoneAddressException
     */
    protected function validatePhoneBinding(): void
    {
        if (!$this->accountManager->getAccount()->bind_phone || !$this->accountManager->getAccount(
            )->bind_phone_address) {
            throw new AccountException("未绑定手机号");
        }

        if (!$this->validatePhoneAddress()) {
            throw new PhoneAddressException("绑定手机号地址无效");
        }
    }

    /**
     * @return bool|null
     */
    protected function validatePhoneAddress(): ?bool
    {
        try {

            return (bool)$this->accountManager->getPhoneConnector()
                ->getPhoneCode($this->accountManager->getAccount()->bind_phone_address);

        } catch (FatalRequestException|RequestException $e) {

            return false;
        }
    }

    /**
     * @param DataCollection $trustedPhones
     * @return Phone|null
     */
    protected function findTrustedPhone(DataCollection $trustedPhones): ?PhoneNumber
    {
        return $trustedPhones->first(function (PhoneNumber $phone) {
            return Str::contains($this->accountManager->getAccount()->bind_phone, $phone->lastTwoDigits);
        });
    }

    /**
     * @param PhoneNumber $phone
     * @return SendPhoneVerificationCode
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    protected function sendPhoneSecurityCode(PhoneNumber $phone): SendPhoneVerificationCode
    {
        return $this->accountManager->sendPhoneSecurityCode($phone->id);
    }

    /**
     * @param PhoneNumber $phone
     * @return VerifyPhoneSecurityCode
     * @throws AttemptBindPhoneCodeException
     * @throws FatalRequestException
     * @throws RequestException
     * @throws VerificationCodeException|\JsonException
     */
    protected function handlePhoneVerification(PhoneNumber $phone): VerifyPhoneSecurityCode
    {
        $code = $this->accountManager->getPhoneConnector()
            ->attemptGetPhoneCode($this->accountManager->getAccount()->bind_phone_address, new PhoneCodeParser());

        return $this->accountManager->verifyPhoneCode($phone->id, $code);
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    protected function fetchDevices(): \Spatie\LaravelData\DataCollection
    {
        return $this->accountManager->fetchDevices();
    }

    protected function fetchPaymentConfig()
    {
        //获取支付方式
        $paymentConfig = $this->accountManager->getPayment();
//
//        $paymentConfig->currentPaymentOption;
    }
}
