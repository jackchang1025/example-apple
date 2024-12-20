<?php

namespace Modules\AppleClient\Service;

use App\Models\Phone;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JsonException;
use Modules\AppleClient\Events\AccountBindPhoneFailEvent;
use Modules\AppleClient\Events\AccountBindPhoneSuccessEvent;
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
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\Device;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Trait\HasNotification;
use Modules\AppleClient\Service\Trait\HasTries;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Modules\PhoneCode\Service\PhoneCodeService;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

class AddSecurityVerifyPhoneService
{
    use HasTries;
    use HasNotification;

    protected ?Phone $phone = null;

    protected array $notInPhones = [];

    private int $attempts = 1;

    public function __construct(
        protected Apple $apple,
        protected PhoneCodeService $phoneCodeService,
        protected Dispatcher $dispatcher,
        protected LoggerInterface $logger,
        protected PhoneCodeParser $phoneCodeParser = new PhoneCodeParser()
    ) {
        $this->withTries(5)->withRetryInterval(1000)->withUseExponentialBackoff();
    }

    /**
     * @return void
     * @throws AccountException
     * @throws BindPhoneException
     * @throws FatalRequestException
     * @throws MaxRetryAttemptsException
     * @throws PhoneNotFoundException
     * @throws RequestException
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->fetchInfo();

        $this->handleAddSecurityVerifyPhone();
    }

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
    public function handleAddSecurityVerifyPhone(): void
    {
        try {

            $this->validateAccount();

            $this->attemptBind();

        } catch (Throwable|Exception $e) {

            $this->handleException($e);

            throw $e;
        }
    }

    public function getPhoneCodeService(): PhoneCodeService
    {
        return $this->phoneCodeService;
    }

    public function getDispatcher(): ?Dispatcher
    {
        return $this->dispatcher;
    }

    public function getApple(): Apple
    {
        return $this->apple;
    }

    protected function fetchInfo(): void
    {
        try {

            $this->updateOrCreatePaymentConfig();

            $this->updateOrCreateDevices();

        } catch (\Exception $e) {

            $this->errorNotification("获取用户信息失败", $e->getMessage());
        }
    }

    /**
     * @return \App\Models\Payment
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function updateOrCreatePaymentConfig(): \App\Models\Payment
    {
        return $this->apple->getWebResource()
            ->getAppleIdResource()
            ->getPaymentResource()
            ->getPayment()
            ->primaryPaymentMethod->updateOrCreate($this->apple->getAccount()->model()->id);
    }

    /**
     * @return Collection
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function updateOrCreateDevices(): Collection
    {
        return $this->apple->getWebResource()
            ->getAppleIdResource()
            ->getDevicesResource()
            ->getDevicesDetails()
            ->toCollection()
            ->map(fn(Device $device) => $device->deviceDetail->updateOrCreate($this->apple->getAccount()->model()->id));
    }

    /**
     * @return void
     * @throws AccountException
     */
    public function validateAccount(): void
    {
        if ($this->apple->getAccount()->getBindPhone()) {
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

                $this->addSecurityVerifyPhone();

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
            sprintf("账号：%s 尝试 %d 次后绑定失败", $this->getApple()->getAccount()->account, $this->attempts)
        );
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    public function refreshAvailablePhone(): Phone
    {
        return $this->phone = $this->getAvailablePhone();
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
     * @throws BindPhoneException
     */
    private function addSecurityVerifyPhone(): SecurityVerifyPhone
    {
        try {

            $response = $this->apple->getWebResource()->getAppleIdResource()->getSecurityPhoneResource(
            )->securityVerifyPhone(
                countryCode: $this->getPhone()->country_code,
                phoneNumber: $this->getPhone()->national_number,
                countryDialCode: $this->getPhone()->country_dial_code
            );

            //为了防止拿到上一次验证码导致错误，这里建议睡眠一段时间再尝试
            usleep($this->getSleepTime($this->attempts, $this->getRetryInterval(), $this->getUseExponentialBackoff()));

            //获取验证码
            $code = $this->getPhoneCodeService()->attemptGetPhoneCode(
                $this->getPhone()->phone_address,
                $this->phoneCodeParser
            );

            $response = $this->apple->getWebResource()->getAppleIdResource()->getSecurityPhoneResource(
            )->securityVerifyPhoneSecurityCode(
                id: $response->phoneNumberVerification->phoneNumber->id,
                phoneNumber: $this->getPhone()->national_number,
                countryCode: $this->getPhone()->country_code,
                countryDialCode: $this->getPhone()->country_dial_code,
                code: $code
            );

            $this->getDispatcher()?->dispatch(
                new AccountBindPhoneSuccessEvent(
                    account: $this->apple->getAccount(),
                    addSecurityVerifyPhone: $this->getPhone(),
                    message: "次数: {$this->attempts} 手机号码: {$this->getPhone()->phone} 绑定成功"
                )
            );

            return $response;

        } catch (\Exception $e) {

            $this->getDispatcher()?->dispatch(
                new AccountBindPhoneFailEvent(
                    account: $this->apple->getAccount(),
                    addSecurityVerifyPhone: $this->getPhone(),
                    message: "次数：{$this->attempts} 手机号码：{$this->getPhone()->phone} 绑定失败 消息: {$e->getMessage()}"
                )
            );
            throw $e;
        }
    }

    /**
     * @return void
     * @throws Throwable
     */
    protected function handleBindSuccess(): void
    {
        $this->successNotification("绑定成功", "次数: {$this->attempts} 手机号码：{$this->getPhone()->phone} 绑定成功");
    }

    protected function handleException(Throwable $exception): void
    {
        if ($this->getPhone()) {

            $status = $exception instanceof PhoneException ? Phone::STATUS_INVALID : Phone::STATUS_NORMAL;
            Phone::where('id', $this->getPhone()->id)->update(['status' => $status]);
            $this->addNotInPhones($this->getPhone()->id);
        }

        $this->errorNotification(
            "绑定失败",
            "次数: {$this->attempts} 手机号码: {$this->getPhone()->phone} 绑定失败 消息: {$exception->getMessage()}"
        );
    }

    public function addNotInPhones(int|string $id): void
    {
        $this->notInPhones[] = $id;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
