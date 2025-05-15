<?php

namespace App\Services;


use App\Models\Phone;
use Illuminate\Http\Request;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;
use App\Models\Account;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneNotFoundException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeException;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Dto\Response\Auth\Auth;
use Weijiajia\SaloonphpAppleClient\DataConstruct\PhoneNumber;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendPhoneVerificationCode;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeSentTooManyTimesException;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendDeviceSecurityCode;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Dto\Response\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Weijiajia\SaloonphpAppleClient\DataConstruct\NullData;
use Illuminate\Support\Facades\Cache;

class AppleClientControllerService
{
    protected Account $apple;

    public function __construct(
        protected readonly Request $request,
    )
    {

    }
    public function getApple(): Account
    {
        $account = base64_decode($this->getGuidByRequest());
        return $this->apple ??= Account::where('appleid', $account)->firstOrFail();
    }

    public function getGuidByRequest(): array|string|null
    {
        return $this->request->cookie('Guid', $this->request->input("Guid"));
    }

    /**
     * @return SignInComplete
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function sign(): SignInComplete
    {
        return $this->getApple()->appleIdResource()->signIn();
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
        return Cache::remember("{$this->getApple()->appleid()}_auth", 60 * 5, function () {
            return $this->getApple()->appleIdResource()->appleAuth();
        });
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
    public function getTrustedPhoneNumber(): PhoneNumber
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
        return $this->getApple()->appleIdResource()->sendPhoneSecurityCode($id);
    }

    /**
     * @return SendDeviceSecurityCode
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sendSecurityCode(): SendDeviceSecurityCode
    {
        return $this->getApple()->appleIdResource()->sendVerificationCode();
    }

    /**
     * @return bool|SecurityVerifyPhone
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function isStolenDeviceProtectionException(): bool|SecurityVerifyPhone
    {
        //从数据库获取号码
        $phone = Phone::where('status', Phone::STATUS_NORMAL)->firstOrFail();

        return $this->getApple()
            ->appleIdResource()
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
     * @throws StolenDeviceProtectionException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws FatalRequestException|PhoneNotFoundException
     */
    public function verifyPhoneCode(string $id, string $code): VerifyPhoneSecurityCode
    {
        return $this->getApple()->appleIdResource()->verifyPhoneVerificationCode($id ,$code);
    }

    /**
     * @param string $code
     * @return NullData
     * @throws StolenDeviceProtectionException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws \JsonException
     * @throws FatalRequestException
     */
    public function verifySecurityCode(string $code): NullData
    {
        return $this->getApple()->appleIdResource()->verifySecurityCode($code);
    }
}

