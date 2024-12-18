<?php

namespace Modules\AppleClient\Service;


use App\Jobs\BindAccountPhone;
use App\Models\Phone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\Auth\Auth;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendDeviceSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

class AppleClientControllerService
{
    protected Apple $apple;

    public function __construct(
        protected readonly Request $request,
        protected readonly AppleBuilder $appleBuilder
    )
    {

    }

    public function getAccount(): Account
    {
        return $this->getApple()->getAccount();
    }

    public function withApple(Apple $apple): static
    {
        $this->apple = $apple;

        return $this;
    }

    public function getApple(): Apple
    {
        return $this->apple ??= $this->appleBuilder->build($this->getGuidByRequest());
    }

    public function getGuidByRequest()
    {
        return $this->request->cookie('Guid', $this->request->input("Guid"));
    }

    public function getGuid(): string
    {
        return $this->getApple()->getAccount()->getSessionId();
    }

    /**
     * @return SignInComplete
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function sign(): SignInComplete
    {
        return $this->getApple()->getWebResource()->getAppleIdResource()->signIn();
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
     */
    public function auth(): Auth
    {
        return $this->getApple()->getWebResource()->getAppleIdResource()->getAuth();
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws RequestException|\JsonException
     */
    public function getTrustedPhoneNumbers(): DataCollection
    {
        return $this->auth()->getTrustedPhoneNumbers();
    }

    /**
     * @return PhoneNumber
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getTrustedPhoneNumber(): DataConstruct\PhoneNumber
    {
        return $this->auth()->getTrustedPhoneNumber();
    }

    /**
     * @param int $id
     * @return SendPhoneVerificationCode
     * @throws VerificationCodeSentTooManyTimesException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sendSms(int $id): SendPhoneVerificationCode
    {
        return $this->getApple()->getWebResource()->getAppleIdResource()->sendPhoneSecurityCode(PhoneNumber::from([
            'id'                 => $id,
            'numberWithDialCode' => '',
            'pushMode'           => '',
            'obfuscatedNumber'   => '',
            'lastTwoDigits'      => '',
        ]));
    }

    /**
     * @return SendDeviceSecurityCode
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sendSecurityCode(): SendDeviceSecurityCode
    {
        return $this->getApple()->getWebResource()->getAppleIdResource()->sendVerificationCode();
    }

    /**
     * @return bool|SecurityVerifyPhone
     * @throws Exception\BindPhoneException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function isStolenDeviceProtectionException(): bool|SecurityVerifyPhone
    {
        //从数据库获取号码
        $phone = Phone::firstOrFail();

        return $this->getApple()
            ->getWebResource()
            ->getAppleIdResource()
            ->getAccountManagerResource()
            ->isStolenDeviceProtectionException(
                countryCode: $phone->country_code,
                phoneNumber: $phone->national_number,
                countryDialCode: $phone->country_dial_code
            );
    }

    /**
     * @param string $id
     * @param string $code
     * @return VerifyPhoneSecurityCode
     * @throws Exception\StolenDeviceProtectionException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws FatalRequestException|Exception\PhoneNotFoundException
     */
    public function verifyPhoneCode(string $id, string $code): VerifyPhoneSecurityCode
    {
        return $this->getApple()->getWebResource()->getAppleIdResource()->verifyPhoneVerificationCode(
            PhoneNumber::from([
                'id'                 => $id,
                'numberWithDialCode' => '',
                'pushMode'           => '',
                'obfuscatedNumber'   => '',
                'lastTwoDigits'      => '',
            ]),
            $code
        );
    }

    /**
     * @param string $code
     * @return DataConstruct\NullData
     * @throws Exception\StolenDeviceProtectionException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws \JsonException
     * @throws FatalRequestException
     */
    public function verifySecurityCode(string $code): DataConstruct\NullData
    {
        return $this->getApple()->getWebResource()->getAppleIdResource()->verifySecurityCode($code);
    }
}

