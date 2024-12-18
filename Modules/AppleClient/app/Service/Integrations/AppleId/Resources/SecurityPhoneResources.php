<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\ErrorException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityPhone\SecurityVerifyPhoneRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityPhone\SecurityVerifyPhoneSecurityCodeRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class SecurityPhoneResources extends BaseResource
{
    /**
     * @param string $countryCode
     * @param string $phoneNumber
     * @param string $countryDialCode
     * @param bool $nonFTEU
     *
     * @return SecurityVerifyPhone
     * @throws BindPhoneException
     * @throws ErrorException
     * @throws FatalRequestException
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeSentTooManyTimesException
     */
    public function securityVerifyPhone(
        string $countryCode,
        string $phoneNumber,
        string $countryDialCode,
        bool $nonFTEU = true
    ): SecurityVerifyPhone {
        try {
            return $this->getConnector()
                ->send(new SecurityVerifyPhoneRequest($countryCode, $phoneNumber, $countryDialCode, $nonFTEU))->dto();
        } catch (RequestException $e) {
            /**
             * @var Response $response
             */
            $response = $e->getResponse();

            if ($response->successful() || $response->status() === 423) {
                return $response->dto();
            }

            $error = $response->getFirstServiceError();

            if ($response->status() === 467) {
                throw  new StolenDeviceProtectionException(
                    response: $response, message: $error?->getMessage(
                ) ?? '已开启“失窃设备保护”，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备'
                );
            }

            if ($error?->getCode() === -28248) {
                throw new PhoneException(
                    response: $response
                );
            }

            if ($error?->getCode() === -22979) {
                throw new VerificationCodeSentTooManyTimesException(
                    response: $response
                );
            }

            //Error Description not available
            if ($error?->getCode() === -22420) {
                throw new ErrorException(
                    response: $response
                );
            }

            if ($error?->getCode() === 'phone.number.already.exists') {
                throw new PhoneNumberAlreadyExistsException(
                    response: $response
                );
            }

            throw new BindPhoneException(
                response: $response
            );
        }
    }

    /**
     * @param int $id
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param string $code
     *
     * @return SecurityVerifyPhone
     * @throws FatalRequestException
     *
     * @throws RequestException
     */
    public function securityVerifyPhoneSecurityCode(
        int $id,
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        string $code
    ): SecurityVerifyPhone {
        return $this->getConnector()
            ->send(
                new SecurityVerifyPhoneSecurityCodeRequest($id, $phoneNumber, $countryCode, $countryDialCode, $code)
            )->dto();
    }
}
