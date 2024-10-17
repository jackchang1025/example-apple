<?php

namespace Modules\AppleClient\Service;

use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginFailEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\AppleClient\Service\DataConstruct\Device\Device;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Response\Response;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

class ProcessAccountImportService
{

    public function __construct(protected Account $account, protected AppleClientService $appleClientService)
    {

    }

    public function handle()
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

            $this->appleClientService->authenticate($this->account->account, $this->account->password);

            Event::dispatch(new AccountLoginSuccessEvent(account: $this->account, description: "登录成功"));
        } catch (\JsonException|FatalRequestException|RequestException $e) {

            Event::dispatch(new AccountLoginFailEvent(account: $this->account, description: $e->getMessage()));

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
     * @throws \JsonException
     */
    protected function auth(): void
    {

        try {

            $this->validatePhoneBinding();

            $response = $this->appleClientService->fetchAuthResponse();

            $phone = $this->findTrustedPhone($response->getTrustedPhoneNumbers());
            if (!$phone) {
                throw new PhoneNotFoundException("未找到该账号绑定的手机号码");
            }

            $this->sendPhoneSecurityCode($response, $phone);

            $this->handlePhoneVerification($phone);
            $this->appleClientService->token();

            Event::dispatch(new AccountAuthSuccessEvent(account: $this->account, description: "授权成功"));

        } catch (\JsonException|Exception\VerificationCodeException|AttemptBindPhoneCodeException|FatalRequestException|RequestException $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $this->account, description: $e->getMessage()));
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
        if (!$this->account->bind_phone || !$this->account->bind_phone_address) {
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

            return (bool)$this->appleClientService->getPhoneConnector()
                ->getPhoneCode($this->account->bind_phone_address);

        } catch (FatalRequestException|RequestException $e) {

            return false;
        }
    }

    /**
     * @param Collection $trustedPhones
     * @return Phone|null
     */
    protected function findTrustedPhone(Collection $trustedPhones): ?Phone
    {
        return $trustedPhones->first(function (Phone $phone) {
            return Str::contains($this->account->bind_phone, $phone->getLastTwoDigits());
        });
    }

    /**
     * @param Response $response
     * @param Phone $phone
     * @return void
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    protected function sendPhoneSecurityCode(Response $response, Phone $phone): void
    {
        if ($response->hasTrustedDevices() || $response->getTrustedPhoneNumbers()->count() >= 2) {
            $this->appleClientService->sendPhoneSecurityCode($phone->getId());
        }
    }

    /**
     * @param Phone $phone
     * @return void
     * @throws AttemptBindPhoneCodeException
     * @throws Exception\StolenDeviceProtectionException
     * @throws Exception\VerificationCodeException
     * @throws FatalRequestException
     * @throws RequestException
     */
    protected function handlePhoneVerification(Phone $phone): void
    {
        $code = $this->appleClientService->getPhoneConnector()
            ->attemptGetPhoneCode($this->account->bind_phone_address, new PhoneCodeParser());

        $this->appleClientService->verifyPhoneCode($phone->getId(), $code);
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    protected function fetchDevices(): \Spatie\LaravelData\DataCollection
    {
        return $this->appleClientService
            ->getDevices()->devices
            ->map(function (Device $device) {
                return $device->updateOrCreate($this->account->id);
            });
    }

    protected function fetchPaymentConfig()
    {
        //获取支付方式
        $paymentConfig = $this->appleClientService->getPayment();
//
//        $paymentConfig->currentPaymentOption;
    }
}
