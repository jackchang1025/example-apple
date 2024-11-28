<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Resources;

use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\DataConstruct\NullData;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Auth\AuthData;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\SendVerificationCode\SendDeviceSecurityCodeData;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\SendVerificationCode\SendPhoneVerificationCodeData;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\SignInCompleteData;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\SignInInitData;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\VerifyPhoneSecurityCode\VerifyPhoneSecurityCodeData;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\Auth;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeSignIn;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeSing;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthRepairComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendTrustedDeviceSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SigninInit;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyTrustedDeviceSecurityCode;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class AuthenticateResources extends BaseResource
{

    /**
     * @param string $a
     * @param string $account
     *
     * @return SignInInitData
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function signInInit(string $a, string $account): SignInInitData
    {
        return $this->getConnector()
            ->send(new SigninInit($a, $account))
            ->dto();
    }


    /**
     * @param string $account
     * @param string $m1
     * @param string $m2
     * @param string $c
     * @param bool $rememberMe
     *
     * @return SignInCompleteData
     * @throws FatalRequestException
     *
     * @throws RequestException
     */
    public function signInComplete(
        string $account,
        string $m1,
        string $m2,
        string $c,
        bool $rememberMe = false
    ): SignInCompleteData {
        return $this->getConnector()->send(
            new SignInComplete(
                account: $account,
                m1: $m1,
                m2: $m2,
                c: $c,
                rememberMe: $rememberMe
            )
        )->dto();
    }

    /**
     * @return Response
     * @throws FatalRequestException
     *
     * @throws RequestException
     */
    public function sign(): Response
    {
        /**
         * @var Config $config
         */
        $config = $this->getConnector()->config();

        return $this->getConnector()->send(
            new AuthorizeSignIn(
                frameId: $this->getConnector()->buildUUid(),
                clientId: $config->getServiceKey(),
                redirectUri: $config->getApiUrl(),
                state: $this->getConnector()->buildUUid(),
            )
        );
    }

    /**
     * @param string $accountName
     * @param string $password
     * @param bool $rememberMe
     *
     * @return Response
     * @throws RequestException
     *
     * @throws FatalRequestException
     */
    public function authorizeSing(string $accountName, string $password, bool $rememberMe = true): Response
    {
        return $this->getConnector()->send(new AuthorizeSing($accountName, $password, $rememberMe));
    }

    /**
     * @return AuthData
     * @throws RequestException
     *
     * @throws FatalRequestException
     */
    public function auth(): AuthData
    {
        return $this->getConnector()->send(new Auth())->dto();
    }

    /**
     * @param string $code
     *
     * @return NullData
     * @throws RequestException
     * @throws VerificationCodeException
     *
     * @throws FatalRequestException
     */
    public function verifySecurityCode(string $code): NullData
    {
        try {

            return $this->getConnector()
                ->send(new VerifyTrustedDeviceSecurityCode($code))
                ->dto();

        } catch (RequestException $e) {
            /**
             * @var Response $response
             */
            $response = $e->getResponse();

            if ($response->status() === 400) {
                throw new VerificationCodeException(
                    $response,
                    $response->getFirstServiceError()?->getMessage() ?? '验证码错误'
                );
            }

            if ($response->status() === 412) {
                return $this->managePrivacyAccept()->dto();
            }

            throw $e;
        }
    }

    /**
     * @return Response
     * @throws RequestException
     *
     * @throws FatalRequestException
     */
    public function managePrivacyAccept(): Response
    {
        return $this->getConnector()->send(new AuthRepairComplete());
    }

    /**
     * @param string $id
     * @param string $code
     *
     * @return VerifyPhoneSecurityCodeData
     * @throws RequestException
     * @throws VerificationCodeException
     *
     * @throws FatalRequestException
     */
    public function verifyPhoneCode(string $id, string $code): VerifyPhoneSecurityCodeData
    {
        try {

            return $this->getConnector()
                ->send(new VerifyPhoneSecurityCode($id, $code))
                ->dto();

        } catch (RequestException $e) {
            /**
             * @var Response $response
             */
            $response = $e->getResponse();

            if ($response->status() === 400) {
                throw new VerificationCodeException(
                    $response,
                    $response->getFirstServiceError()?->getMessage() ?? '验证码错误'
                );
            }

            if ($response->status() === 412) {
                $this->managePrivacyAccept();

                return $response->dto();
            }

            throw $e;
        }


    }

    /**
     * @return SendDeviceSecurityCodeData
     * @throws RequestException
     *
     * @throws FatalRequestException
     */
    public function sendSecurityCode(): SendDeviceSecurityCodeData
    {
        return $this->getConnector()->send(new SendTrustedDeviceSecurityCode())->dto();
    }

    /**
     * @param int $id
     *
     * @return SendPhoneVerificationCodeData
     * @throws RequestException
     * @throws VerificationCodeSentTooManyTimesException
     *
     * @throws FatalRequestException
     */
    public function sendPhoneSecurityCode(int $id): SendPhoneVerificationCodeData
    {
        try {

            return $this->getConnector()->send(new SendPhoneSecurityCode($id))->dto();

        } catch (RequestException $e) {

            /**
             * @var Response $response
             */
            $response = $e->getResponse();

            if ($response->status() === 423) {
                throw new VerificationCodeSentTooManyTimesException(
                    $response,
                    $response->getFirstServiceError()?->getMessage() ?? '验证码发送次数过多'
                );
            }

            throw $e;
        }
    }
}
