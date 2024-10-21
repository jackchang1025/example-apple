<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use JsonException;
use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\ErrorException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleId\AppleIdConnector;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Payment;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityDevices;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityVerifyPhone;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityVerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Token;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AuthenticatePassword;
use Modules\AppleClient\Service\Integrations\AppleId\Request\Bootstrap;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait AppleId
{
    /**
     * @throws RequestException
     * @throws FatalRequestException
     *
     * @return Response
     */
    public function bootstrap(): Response
    {
        return $this->getAppleIdConnector()->send(new Bootstrap());
    }

    abstract public function getAppleIdConnector(): AppleIdConnector;

    /**
     * @param string $password
     *
     * @throws RequestException
     * @throws FatalRequestException
     *
     * @return Response
     */
    public function authenticatePassword(string $password): Response
    {
        return $this->getAppleIdConnector()
            ->send(new AuthenticatePassword($password));
    }

    /**
     * @throws RequestException
     * @throws FatalRequestException
     *
     * @return Response
     */
    public function token(): Response
    {
        return $this->getAppleIdConnector()->send(new Token());
    }

    /**
     * @param string $countryCode
     * @param string $phoneNumber
     * @param string $countryDialCode
     * @param bool   $nonFTEU
     *
     * @return Response
     * @throws RequestException
     * @throws PhoneException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws ErrorException
     * @throws PhoneNumberAlreadyExistsException
     * @throws StolenDeviceProtectionException
     * @throws BindPhoneException|FatalRequestException
     *
     */
    public function securityVerifyPhone(
        string $countryCode,
        string $phoneNumber,
        string $countryDialCode,
        bool $nonFTEU = true
    ): Response {
        try {
            return $this->getAppleIdConnector()
                ->send(new SecurityVerifyPhone($countryCode, $phoneNumber, $countryDialCode, $nonFTEU));
        } catch (RequestException $e) {
            /**
             * @var Response $response
             */
            $response = $e->getResponse();

            if ($response->successful() || $response->status() === 423) {
                return $response;
            }

            $error = $response->getFirstServiceError();

            if ($response->status() === 467) {
                throw  new StolenDeviceProtectionException(response: $response,message: $error?->getMessage() ?? '已开启“失窃设备保护”，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备');
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
     * @param int    $id
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param string $code
     *
     * @throws RequestException
     * @throws FatalRequestException
     *
     * @return Response
     */
    public function securityVerifyPhoneSecurityCode(
        int $id,
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        string $code
    ): Response {
        return $this->getAppleIdConnector()
            ->send(new SecurityVerifyPhoneSecurityCode($id, $phoneNumber, $countryCode, $countryDialCode, $code));
    }

    /**
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function securityDevices(): Response
    {
        return $this->getAppleIdConnector()
            ->send(new SecurityDevices());
    }

    /**
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function payment(): Response
    {
        return $this->getAppleIdConnector()
            ->send(new Payment());
    }
}
