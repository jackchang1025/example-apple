<?php

namespace Modules\AppleClient\Service\Trait;

use App\Models\Phone;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\DataConstruct\ValidatePassword\ValidatePassword;
use Modules\AppleClient\Service\Exception\AppleClientException;
use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasValidateStolenDeviceProtection
{

    /**
     * Validates stolen device protection by authenticating with Apple
     * and attempting to verify the phone through security measures.
     *
     * @return SecurityVerifyPhone|bool The result of the security verification attempt.
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws \JsonException
     */
    protected function validateStolenDeviceProtection(): SecurityVerifyPhone|bool
    {
        $this->authenticateApple();

        return $this->attemptSecurityVerifyPhone();
    }

    /**
     * @return ValidatePassword
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function authenticateApple(): ValidatePassword
    {
        $this->getToken();

        return $this->getValidatePassword();
    }

    /**
     * Verifies the phone code and checks for stolen device protection.
     *
     * This method sends a phone code verification request to the Apple client using the provided ID and code.
     * Subsequently, it validates stolen device protection to ensure security.
     *
     * @param string $id The identifier for the phone verification process.
     * @param string $code The verification code received on the phone.
     *
     * @return SecurityVerifyPhone|bool True if the phone code is verified and the device is not marked as stolen; false otherwise.
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws \JsonException
     */
    public function verifyPhoneCodeAndValidateStolenDeviceProtection(string $id, string $code): SecurityVerifyPhone|bool
    {
        $this->verifyPhoneCode($id, $code);

        return $this->validateStolenDeviceProtection();
    }

    /**
     * Verifies the provided security code using the Apple client and then validates stolen device protection.
     *
     * @param string $code The security code to be verified.
     *
     * @return SecurityVerifyPhone|bool The result of the stolen device protection validation process.
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws \JsonException
     */
    public function verifySecurityCodeAndValidateStolenDeviceProtection(string $code): SecurityVerifyPhone|bool
    {
        $this->verifySecurityCode($code);

        return $this->validateStolenDeviceProtection();
    }

    /**
     * @return SecurityVerifyPhone|bool
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     * @throws BindPhoneException
     */
    protected function attemptSecurityVerifyPhone(): SecurityVerifyPhone|bool
    {
        try {

            $phone = Phone::firstOrFail();

            return $this->securityVerifyPhone(
                countryCode: $phone->country_code,
                phoneNumber: $phone->national_number,
                countryDialCode: $phone->country_dial_code
            );

        } catch (AppleClientException $e) {

            if ($e instanceof StolenDeviceProtectionException) {
                throw $e;
            }

            return true;
        }
    }
}
