<?php

namespace App\Services;

use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginFailEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Models\Payment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use JsonException;
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
        $this->tries = 3;
        $this->retryInterval         = 5;
        $this->useExponentialBackoff = true;
    }

    /**
     * @return void
     * @throws AccountException
     * @throws FatalRequestException
     * @throws MaxRetryAttemptsException
     * @throws PhoneAddressException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws JsonException
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
     * @throws JsonException
     */
    protected function sign(): void
    {

        try {

            $this->accountManager->sign();

            Event::dispatch(
                new AccountLoginSuccessEvent(account: $this->accountManager->getAccount(), description: "登录成功")
            );

            $this->errorNotification("登录成功", "{$this->accountManager->getAccount()->account} 登录成功");

        } catch (JsonException|FatalRequestException|RequestException $e) {

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
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws FatalRequestException
     * @throws PhoneAddressException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws JsonException|MaxRetryAttemptsException
     */
    protected function auth(): void
    {

        try {

            $this->validatePhoneBinding();

            $auth = $this->accountManager->auth();

            $phone = $this->filterTrustedPhone($auth->getTrustedPhoneNumbers());

            $this->attemptVerifyPhoneCode($phone);

            $this->accountManager->token();

            Event::dispatch(
                new AccountAuthSuccessEvent(account: $this->accountManager->getAccount(), description: "授权成功")
            );

            $this->errorNotification("授权成功", "{$this->accountManager->getAccount()->account} 授权成功");

        } catch (JsonException|VerificationCodeException|FatalRequestException|RequestException|MaxRetryAttemptsException $e) {

            Event::dispatch(
                new AccountAuthFailEvent(account: $this->accountManager->getAccount(), description: $e->getMessage())
            );

            $this->errorNotification("授权失败", "{$this->accountManager->getAccount()->account} {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * @return void
     * @throws AccountException
     * @throws PhoneAddressException
     */
    protected function validatePhoneBinding(): void
    {
        $account = $this->accountManager->getAccount();

        if (empty($account->bind_phone)) {
            throw new AccountException("未绑定手机号");
        }

        if (empty($account->bind_phone_address)) {
            throw new AccountException("未绑定手机号地址");
        }

        if (!$this->validatePhoneAddress()) {
            throw new PhoneAddressException("手机号地址无效");
        }
    }

    /**
     * @return bool|null
     */
    protected function validatePhoneAddress(): ?bool
    {
        try {

            return (bool)$this->accountManager->getPhoneCodeService()
                ->getPhoneCode($this->accountManager->getAccount()->bind_phone_address);

        } catch (FatalRequestException|RequestException $e) {

            return false;
        }
    }

    /**
     * @param DataCollection $trustedPhones
     * @return DataCollection
     * @throws JsonException
     * @throws PhoneNotFoundException
     */
    protected function filterTrustedPhone(DataCollection $trustedPhones): DataCollection
    {
        $phoneList = $trustedPhones->filter(function (PhoneNumber $phone) {
            return Str::contains($this->accountManager->getAccount()->bind_phone, $phone->lastTwoDigits);
        });

        if ($phoneList->count() === 0) {
            throw new PhoneNotFoundException(sprintf("%s:%s", '该账号未绑定该手机号码，无法授权登陆', json_encode([
                'account'       => $this->accountManager->getAccount()->toArray(),
                'trusted_phone' => $trustedPhones->toArray(),
            ], JSON_THROW_ON_ERROR)));
        }

        return $phoneList;
    }

    /**
     * @param DataCollection $phoneList
     * @return VerifyPhoneSecurityCode
     * @throws FatalRequestException
     * @throws JsonException
     * @throws MaxRetryAttemptsException
     * @throws RequestException
     */
    protected function attemptVerifyPhoneCode(DataCollection $phoneList): VerifyPhoneSecurityCode
    {

        for ($attempts = 1; $attempts <= $this->getTries(); $attempts++) {

            if ($verifyPhoneSecurityCode = $this->foreachPhoneListVerifyPhoneCode($phoneList, $attempts)) {
                return $verifyPhoneSecurityCode;
            }
        }

        throw new MaxRetryAttemptsException("最大尝试次数:{$this->tries}");
    }

    /**
     * @param DataCollection $phoneList
     * @param int $attempts
     * @return VerifyPhoneSecurityCode|void
     * @throws FatalRequestException
     * @throws JsonException
     * @throws RequestException
     */
    protected function foreachPhoneListVerifyPhoneCode(
        DataCollection $phoneList,
        int $attempts = 1
    ): ?VerifyPhoneSecurityCode {
        foreach ($phoneList as $phone) {

            try {

                /**
                 * @var PhoneNumber $phone
                 */
                $this->sendPhoneSecurityCode($phone);

                //为了防止拿到上一次验证码导致错误，这里建议睡眠一段时间再尝试
                usleep($this->getSleepTime($attempts, $this->getRetryInterval(), false));

                return $this->handlePhoneVerification($phone);

            } catch (VerificationCodeException|AttemptBindPhoneCodeException $e) {

                $message = "phone id: {$phone->id} 第：{$attempts} 次授权失败:{$e->getMessage()} 剩余尝试次数: ".$this->getTries(
                    ) - $attempts;

                Event::dispatch(
                    new AccountAuthFailEvent(
                        account: $this->accountManager->getAccount(), description: $message
                    )
                );

                $this->errorNotification("授权失败", $message);
            }
        }

        return null;
    }

    /**
     * @param PhoneNumber $phone
     * @return SendPhoneVerificationCode
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
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
     * @throws VerificationCodeException|JsonException
     */
    protected function handlePhoneVerification(PhoneNumber $phone): VerifyPhoneSecurityCode
    {
        $code = $this->accountManager->getPhoneCodeService()
            ->attemptGetPhoneCode($this->accountManager->getAccount()->bind_phone_address, new PhoneCodeParser());

        return $this->accountManager->verifyPhoneCode($phone->id, $code);
    }

    /**
     * @return DataCollection <int|Devices>
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    protected function fetchDevices(): DataCollection
    {
        return $this->accountManager->updateOrCreateDevices();
    }

    /**
     * @return Payment
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    protected function fetchPaymentConfig(): Payment
    {
        return $this->accountManager->fetchPaymentConfig();
    }
}
