<?php

namespace App\Apple\Service\Trait;

use App\Apple\Service\Exception\MaxRetryAttemptsException;
use Exception;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Weijiajia\Exception\AccountLockoutException;
use Weijiajia\Exception\AppleClientException;
use Weijiajia\Exception\BindPhoneException;
use Weijiajia\Exception\ErrorException;
use Weijiajia\Exception\PhoneException;
use Weijiajia\Exception\PhoneNumberAlreadyExistsException;
use Weijiajia\Exception\StolenDeviceProtectionException;
use Weijiajia\Exception\VerificationCodeException;
use Weijiajia\Exception\VerificationCodeSentTooManyTimesException;
use Weijiajia\Response\Response;

trait HasAuth
{
    /**
     * Verifies the phone code and checks for stolen device protection.
     *
     * This method sends a phone code verification request to the Apple client using the provided ID and code.
     * Subsequently, it validates stolen device protection to ensure security.
     *
     * @param string $id The identifier for the phone verification process.
     * @param string $code The verification code received on the phone.
     *
     * @return Response|bool True if the phone code is verified and the device is not marked as stolen; false otherwise.
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws \JsonException
     */
    public function verifyPhoneCode(string $id,string $code): Response|bool
    {
        $this->appleClient->verifyPhoneCode($id,$code);
        return $this->validateStolenDeviceProtection();
    }

    /**
     * Verifies the provided security code using the Apple client and then validates stolen device protection.
     *
     * @param string $code The security code to be verified.
     *
     * @return Response|bool The result of the stolen device protection validation process.
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws \JsonException
     */
    public function verifySecurityCode(string $code): Response|bool
    {
       $this->appleClient->verifySecurityCode($code);

        return $this->validateStolenDeviceProtection();
    }

    /**
     * Authenticates with Apple using the provided credentials.
     *
     * This function retrieves an authentication token from the Apple client and subsequently
     * attempts to authenticate using the password associated with the account fetched from cache.
     *
     * @throws Exception If there's an issue with authentication or fetching the account.
     * @throws RequestException
     * @throws FatalRequestException
     */
    protected function authenticateApple(): void
    {
        $this->appleClient->token();
        $this->appleClient->authenticatePassword($this->getAccountByCache()->password);
    }

    /**
     * Validates stolen device protection by authenticating with Apple
     * and attempting to verify the phone through security measures.
     *
     * @return Response|bool The result of the security verification attempt.
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws \JsonException
     */
    protected function validateStolenDeviceProtection(): Response|bool
    {
        $this->authenticateApple();

        return $this->attemptSecurityVerifyPhone();
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
     * @return Response The response from the Apple security service after attempting to verify the phone number.
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws StolenDeviceProtectionException
     * @throws \JsonException
     * @throws RequestException
     * @throws AccountLockoutException
     * @throws ErrorException
     */
    protected function sendBindRequest(
        string $countryCode,
        string $phoneNumber,
        string $countryDialCode,
        bool $nonFTEU = true
    ): Response
    {
        return $this->appleClient->securityVerifyPhone(
            countryCode:$countryCode,
            phoneNumber: $phoneNumber,
            countryDialCode: $countryDialCode,
            nonFTEU: $nonFTEU
        );
    }


    /**
     * Attempts to verify phone security by sending a bind request.
     *
     * This method iterates through a number of attempts defined by `$this->tries`,
     * each time attempting to fetch an available phone number, modify its status,
     * and then send a binding request using the phone's details. If a `StolenDeviceProtectionException`
     * is caught during this process, it is re-thrown to the caller. Any other exceptions
     * are caught and handled, allowing the method to continue attempting until the maximum
     * number of tries is reached. If all attempts fail, a `MaxRetryAttemptsException` is thrown.
     *
     * @return Response|bool Returns true if the phone binding request was successful within the given tries.
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws \JsonException
     */
    protected function attemptSecurityVerifyPhone(): Response|bool
    {
        try {

            $phone = $this->fetchAvailablePhone();

            return $this->sendBindRequest(
                countryCode: $phone->country_code,
                phoneNumber: $phone->national_number,
                countryDialCode: $phone->country_dial_code
            );

        } catch (AppleClientException $e) {

            if ($e instanceof StolenDeviceProtectionException){
                throw $e;
            }

            return true;
        }
    }
}
