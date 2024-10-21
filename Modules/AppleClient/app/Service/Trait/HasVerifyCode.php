<?php

namespace Modules\AppleClient\Service\Trait;

use JsonException;
use Modules\AppleClient\Service\DataConstruct\NullData;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendDeviceSecurityCode;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\DataConstruct\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\ErrorException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasVerifyCode
{
    /**
     * @param string $id
     * @param string $code
     * @return VerifyPhoneSecurityCode
     * @throws FatalRequestException
     * @throws RequestException
     * @throws VerificationCodeException|JsonException
     */
    public function verifyPhoneCode(string $id, string $code): VerifyPhoneSecurityCode
    {
        return VerifyPhoneSecurityCode::fromResponse($this->getClient()->verifyPhoneCode($id, $code));
    }

    /**
     * @param int $id
     * @return SendPhoneVerificationCode
     * @throws FatalRequestException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws RequestException|JsonException
     */
    public function sendPhoneSecurityCode(int $id): SendPhoneVerificationCode
    {
        return SendPhoneVerificationCode::fromResponse($this->getClient()->sendPhoneSecurityCode($id));
    }

    /**
     * @return SendDeviceSecurityCode
     * @throws FatalRequestException
     * @throws RequestException|JsonException
     */
    public function sendSecurityCode(): SendDeviceSecurityCode
    {
        return SendDeviceSecurityCode::fromResponse($this->getClient()->sendSecurityCode());
    }

    /**
     * @param string $code
     * @return NullData
     * @throws FatalRequestException
     * @throws RequestException
     * @throws VerificationCodeException|JsonException
     */
    public function verifySecurityCode(string $code): NullData
    {
        return NullData::fromResponse($this->getClient()->verifySecurityCode($code));
    }

    /**
     * Sends a bind request to verify a phone number with Apple's security service.
     *
     * This function initiates a process to bind a user's phone number by sending a verification request
     * to Apple's security service using the provided country code, phone number, and country dial code.
     * The `nonFTEU` parameter specifies whether the request is for a non-FTEU (Free Trade Economic Union) region.
     *
     * @param string $countryCode The ISO country code where the phone number is registered.
     * @param string $phoneNumber The phone number to be verified and bound.
     * @param string $countryDialCode The dialing code prefix for the specified country.
     * @param bool $nonFTEU Optional; indicates if the request is for a non-Free Trade Economic Union region. Defaults to true.
     *
     * @return SecurityVerifyPhone The response from the Apple security service after attempting to verify the phone number.
     * @throws ErrorException
     * @throws FatalRequestException
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws JsonException
     * @throws BindPhoneException
     */
    public function securityVerifyPhone(
        string $countryCode,
        string $phoneNumber,
        string $countryDialCode,
        bool $nonFTEU = true
    ): SecurityVerifyPhone {
        return SecurityVerifyPhone::fromResponse(
            $this->getClient()->securityVerifyPhone(
                countryCode: $countryCode,
                phoneNumber: $phoneNumber,
                countryDialCode: $countryDialCode,
                nonFTEU: $nonFTEU
            )
        );
    }

    /**
     * @param int $id
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param string $code
     * @return SecurityVerifyPhone
     * @throws FatalRequestException
     * @throws JsonException|VerificationCodeException
     */
    public function securityVerifyPhoneSecurityCode(
        int $id,
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        string $code
    ): SecurityVerifyPhone {
        try {

            return SecurityVerifyPhone::fromResponse(
                $this->client->securityVerifyPhoneSecurityCode(
                    id: $id,
                    phoneNumber: $phoneNumber,
                    countryCode: $countryCode,
                    countryDialCode: $countryDialCode,
                    code: $code
                )
            );

        } catch (RequestException $e) {

            throw new VerificationCodeException($e->getResponse());
        }
    }
}
