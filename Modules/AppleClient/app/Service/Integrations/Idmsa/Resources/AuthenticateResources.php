<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Resources;

use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\DataConstruct\NullData;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Request\SignIn\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\Auth\Auth;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendDeviceSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete as SignInCompleteResponse;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInInit;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeSignInRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeSingRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthRepairCompleteRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendPhoneSecurityCodeRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendTrustedDeviceSecurityCodeRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SignInCompleteRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SignInInitRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyPhoneSecurityCodeRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyTrustedDeviceSecurityCodeRequest;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class AuthenticateResources extends BaseResource
{

    /**
     * @param string $a
     * @param string $account
     *
     * @return SignInInit
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function signInInit(string $a, string $account): SignInInit
    {
        return $this->getConnector()
            ->send(new SignInInitRequest($a, $account))
            ->dto();
    }


    /**
     * @param SignInComplete $data
     * @return SignInCompleteResponse
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function signInComplete(SignInComplete $data): SignInCompleteResponse
    {
        return $this->getConnector()->send(
            new SignInCompleteRequest($data)
        )->dto();
    }

    /**
     * @param string $frameId
     * @param string $clientId
     * @param string $redirectUri
     * @param string $state
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sign(string $frameId, string $clientId, string $redirectUri, string $state): Response
    {
        return $this->getConnector()->send(
            new AuthorizeSignInRequest(
                frameId: $frameId,
                clientId: $clientId,
                redirectUri: $redirectUri,
                state: $state,
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
        return $this->getConnector()->send(new AuthorizeSingRequest($accountName, $password, $rememberMe));
    }

    /**
     * @return Auth
     * @throws RequestException
     *
     * @throws FatalRequestException
     */
    public function auth(): Auth
    {
        return $this->getConnector()->send(new AuthRequest())->dto();
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
                ->send(new VerifyTrustedDeviceSecurityCodeRequest($code))
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
        return $this->getConnector()->send(new AuthRepairCompleteRequest());
    }

    /**
     * @param string $id
     * @param string $code
     *
     * @return VerifyPhoneSecurityCode
     * @throws RequestException
     * @throws VerificationCodeException
     *
     * @throws FatalRequestException
     */
    public function verifyPhoneCode(string $id, string $code): VerifyPhoneSecurityCode
    {
        try {

            return $this->getConnector()
                ->send(new VerifyPhoneSecurityCodeRequest($id, $code))
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
     * @return SendDeviceSecurityCode
     * @throws RequestException
     *
     * @throws FatalRequestException
     */
    public function sendSecurityCode(): SendDeviceSecurityCode
    {
        return $this->getConnector()->send(new SendTrustedDeviceSecurityCodeRequest())->dto();
    }

    /**
     * @param int $id
     *
     * @return SendPhoneVerificationCode
     * @throws FatalRequestException
     * @throws RequestException
     * @throws VerificationCodeSentTooManyTimesException
     */
    public function sendPhoneSecurityCode(int $id): SendPhoneVerificationCode
    {
        try {

            return $this->getConnector()->send(new SendPhoneSecurityCodeRequest($id))->dto();

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
