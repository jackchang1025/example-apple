<?php

namespace Modules\AppleClient\Service;


use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Jobs\BindAccountPhone;
use App\Models\SecuritySetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\Exception\UnauthorizedException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Response\Response;
use Modules\IpAddress\Service\IpService;
use Modules\IpProxyManager\Service\ProxyService;
use Modules\Phone\Services\PhoneNumberFactory;
use Propaganistas\LaravelPhone\Exceptions\NumberFormatException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

readonly class AppleClientControllerService
{

    public function __construct(
        protected AppleClientService $appleClientService,
        protected IpService          $ipService,
        protected ProxyService       $proxyService,
        protected PhoneNumberFactory $phoneNumberFactory
    )
    {

    }

    public function getGuid(): string
    {
        return $this->appleClientService->getSessionId();
    }

    /**
     * @param string $accountName
     * @return string
     */
    protected function validateAccount(string $accountName): string
    {
        $validator = Validator::make(['email' => $accountName], [
            'email' => 'email',
        ]);

        // 不是有效的邮箱,那就是手机号
        if ($validator->fails()) {
            return $this->formatPhone($accountName);
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
     * @throws UnauthorizedException
     * @throws RequestException
     * @throws \JsonException
     * @throws FatalRequestException
     */
    public function verifyAccount(string $accountName,string $password): Response
    {
        $accountName = $this->validateAccount($accountName);

        $response = $this->appleClientService->sign($accountName,$password);

        $account = $this->appleClientService->getAccountByCache();

        Event::dispatch(new AccountLoginSuccessEvent(account: $account,description: "登录成功"));

        return $response;
    }

    /**
     * @return Collection
     * @throws FatalRequestException
     * @throws RequestException
     * @throws UnauthorizedException
     */
    public function getPhoneLists(): Collection
    {
        return $this->appleClientService->getPhoneLists();
    }

    /**
     * @return Phone|null
     * @throws FatalRequestException
     * @throws RequestException
     * @throws UnauthorizedException
     */
    public function getTrustedPhoneNumber(): ?Phone
    {
        return $this->appleClientService->getTrustedPhoneNumber();
    }

    /**
     * @return string|null
     */
    protected function getCountryCode():?string
    {
        return SecuritySetting::first()?->configuration['country_code'] ?? null;
    }

    /**
     * @param string $phone
     * @return string
     */
    protected function formatPhone(string $phone): string
    {
        try {

            return $this->phoneNumberFactory->create($phone)->format();

        } catch (NumberFormatException $e) {

            return $phone;
        }
    }

    /**
     * @param int $id
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sendSms(int $id): Response
    {
        return $this->appleClientService->sendPhoneSecurityCode($id);
    }

    /**
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sendSecurityCode(): Response
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

            $response = $this->appleClientService->verifyPhoneCodeAndValidateStolenDeviceProtection($id, $code);

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


            $response = $this->appleClientService->verifySecurityCodeAndValidateStolenDeviceProtection($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "安全码验证成功 code:{$code}"));

        } catch (RequestException $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $account,description: $e->getMessage()));
            throw $e;
        }

        $this->dispatchBindAccountPhone();

        return $response;
    }
}

