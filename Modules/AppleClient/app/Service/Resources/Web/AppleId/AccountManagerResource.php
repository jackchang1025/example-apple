<?php

namespace Modules\AppleClient\Service\Resources\Web\AppleId;

use Modules\AppleClient\Service\Exception\AppleClientException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Token\Token;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\ValidatePassword\ValidatePassword;
use Saloon\Exceptions\Request\FatalRequestException;

class AccountManagerResource
{
    protected ?ValidatePassword $validatePassword = null;

    protected ?Token $token = null;

    public function __construct(protected AppleIdResource $appleIdResource)
    {

    }

    public function getAppleIdResource(): AppleIdResource
    {
        return $this->appleIdResource;
    }

    /**
     * @return ValidatePassword
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function authenticatePassword(): ValidatePassword
    {
        return $this->validatePassword ??= $this->getAppleIdResource()->getAppleIdConnector()->getAuthenticateResources(
        )->authenticatePassword(
            $this->getAppleIdResource()->getWebResource()->getApple()->getAccount()->password
        );
    }

    /**
     * @return Token
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function token(): Token
    {
        return $this->token ??= $this->getAppleIdResource()->getAppleIdConnector()->getAuthenticateResources()->token();
    }

    /**
     * @param string $countryCode
     * @param string $phoneNumber
     * @param string $countryDialCode
     * @return SecurityVerifyPhone|bool
     * @throws FatalRequestException
     * @throws \Modules\AppleClient\Service\Exception\BindPhoneException|\Saloon\Exceptions\Request\RequestException
     */
    public function isStolenDeviceProtectionException(
        string $countryCode,
        string $phoneNumber,
        string $countryDialCode,
    ): SecurityVerifyPhone|bool {
        try {

            $this->token();
            $this->authenticatePassword();

            return $this->getAppleIdResource()->getSecurityPhoneResource()->securityVerifyPhone(
                countryCode: $countryCode,
                phoneNumber: $phoneNumber,
                countryDialCode: $countryDialCode
            );

        } catch (AppleClientException $e) {

            return $e instanceof StolenDeviceProtectionException;
        }
    }
}
