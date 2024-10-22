<?php

namespace Modules\AppleClient\Service\Trait;

use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountBindPhoneSuccessEvent;
use App\Models\Phone;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use JsonException;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\AppleClientException;
use Modules\AppleClient\Service\Exception\BindPhoneCodeException;
use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\ErrorException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

trait HasBindPhone
{
    protected ?Phone $phone = null;

    protected array $notInPhones = [];

    private int $attempts = 1;

    /**
     * @return void
     * @throws AccountException
     * @throws BindPhoneException
     * @throws FatalRequestException
     * @throws JsonException
     * @throws MaxRetryAttemptsException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws Throwable
     */
    public function handleBindPhone(): void
    {
        try {

            $this->validateAccount();

            $this->fetchInfo();

            $this->attemptBind();

        } catch (Throwable|Exception $e) {

            $this->handleException($e);

            throw $e;
        }
    }

    protected function fetchInfo(): void
    {
        try {

            $this->fetchDevices();

            $this->fetchPaymentConfig();

        } catch (Exception $e) {

            $this->errorNotification("获取用户信息失败", $e->getMessage());
        }
    }

    /**
     * @return void
     * @throws AccountException
     */
    public function validateAccount(): void
    {
        if ($this->getAccount()->bind_phone || $this->getAccount()->bind_phone_address) {
            throw new AccountException("该账户已绑定手机号");
        }
    }

    /**
     * @return void
     * @throws BindPhoneException
     * @throws FatalRequestException
     * @throws JsonException
     * @throws MaxRetryAttemptsException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws Throwable
     */
    private function attemptBind(): void
    {
        $tries = $this->getTries() ?: 1;

        for ($this->attempts = 1; $this->attempts <= $tries; $this->attempts++) {

            try {

                $this->refreshAvailablePhone();

                $this->bindPhoneToAccount();

                $this->handleBindSuccess();

                return;
            } catch (AppleClientException|AttemptBindPhoneCodeException|BindPhoneCodeException $e) {
                $this->handleException($e);
                if ($e instanceof StolenDeviceProtectionException) {
                    throw $e;
                }
            }
        }

        throw new MaxRetryAttemptsException(
            sprintf("账号：%s 尝试 %d 次后绑定失败", $this->getAccount()->account, $this->attempts)
        );
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    public function setPhone(?Phone $phone): void
    {
        $this->phone = $phone;
    }

    public function refreshAvailablePhone(): Phone
    {
        $this->phone = $this->getAvailablePhone();

        return $this->phone;
    }

    protected function getAvailablePhone(): Phone
    {
        return DB::transaction(function () {
            $phone = Phone::query()
                ->where('status', Phone::STATUS_NORMAL)
                ->whereNotNull(['phone_address', 'phone'])
                ->whereNotIn('id', $this->getNotInPhones())
                ->lockForUpdate()
                ->firstOrFail();

            $phone->update(['status' => Phone::STATUS_BINDING]);

            return $phone;
        });
    }

    public function getNotInPhones(): array
    {
        return $this->notInPhones;
    }

    public function setNotInPhones(array $notInPhones): void
    {
        $this->notInPhones = $notInPhones;
    }

    /**
     * @return SecurityVerifyPhone
     * @throws AttemptBindPhoneCodeException
     * @throws ErrorException
     * @throws FatalRequestException
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws JsonException
     * @throws BindPhoneException
     */
    private function bindPhoneToAccount(): SecurityVerifyPhone
    {
        $response = $this->securityVerifyPhone(
            countryCode: $this->getPhone()->country_code,
            phoneNumber: $this->getPhone()->national_number,
            countryDialCode: $this->getPhone()->country_dial_code
        );

        //为了防止拿到上一次验证码导致错误，这里建议睡眠一段时间再尝试
        usleep($this->getSleepTime($this->attempts, $this->getRetryInterval(), $this->getUseExponentialBackoff()));

        $code = $this->getPhoneConnector()->attemptGetPhoneCode(
            $this->getPhone()->phone_address,
            new PhoneCodeParser()
        );

        return $this->securityVerifyPhoneSecurityCode(
            id: $response->phoneNumberVerification->phoneNumber->id,
            phoneNumber: $this->getPhone()->national_number,
            countryCode: $this->getPhone()->country_code,
            countryDialCode: $this->getPhone()->country_dial_code,
            code: $code
        );
    }

    /**
     * @return void
     * @throws PhoneNotFoundException
     * @throws Throwable
     */
    protected function handleBindSuccess(): void
    {
        if (!$this->getPhone()) {
            throw new PhoneNotFoundException("绑定失败，手机号码获取失败");
        }

        DB::transaction(function () {

            $this->getAccount()->update([
                'bind_phone'         => $this->getPhone()->phone,
                'bind_phone_address' => $this->getPhone()->phone_address,
            ]);

            $this->getPhone()?->update(['status' => Phone::STATUS_BOUND]);
        });

        $message = "账号：{$this->getAccount()->account} 绑定成功 手机号码：{$this->getPhone()->phone}";

        Event::dispatch(new AccountBindPhoneSuccessEvent(account: $this->getAccount(), description: $message));
        $this->successNotification("绑定成功", $message);
    }

    protected function handleException(Throwable $exception): void
    {
        $this->handlePhoneException($exception);

        $message = "账号：{$this->getAccount()->account} 手机号码：{$this->getPhone()?->phone} 重试次数： {$this->attempts} 错误消息：{$exception->getMessage()}";
        $this->getAccount() && Event::dispatch(
            new AccountBindPhoneFailEvent(account: $this->getAccount(), description: $message)
        );
        $this->errorNotification("绑定失败", $message);
    }

    protected function handlePhoneException(Throwable $exception): void
    {
        if (!$this->getPhone()) {
            return;
        }

        $status = $exception instanceof PhoneException ? Phone::STATUS_INVALID : Phone::STATUS_NORMAL;
        Phone::where('id', $this->getPhone()->id)->update(['status' => $status]);
        $this->addNotInPhones($this->getPhone()->id);
    }

    public function addNotInPhones(int|string $id): void
    {
        $this->notInPhones[] = $id;
    }
}
