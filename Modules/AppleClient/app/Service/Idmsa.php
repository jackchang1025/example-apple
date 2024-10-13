<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\Auth;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeSing;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthRepairComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendTrustedDeviceSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\Signin;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SigninInit;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyTrustedDeviceSecurityCode;
use Modules\AppleClient\Service\Response\Response;
use InvalidArgumentException;
use JsonException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait Idmsa
{
    /**
     * @param string $a
     * @param string $account
     *
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @return Response
     */
    public function init(string $a, string $account): Response
    {
        $response = $this->getIdmsaConnector()->send(new SigninInit($a, $account));

        if (empty($response->json('salt'))) {
            throw new InvalidArgumentException("salt IS EMPTY");
        }

        if (empty($response->json('b'))) {
            throw new InvalidArgumentException("b IS EMPTY");
        }

        if (empty($response->json('c'))) {
            throw new InvalidArgumentException("c IS EMPTY");
        }

        if (empty($response->json('iteration'))) {
            throw new InvalidArgumentException("iteration IS EMPTY");
        }

        if (empty($response->json('protocol'))) {
            throw new InvalidArgumentException("protocol IS EMPTY");
        }

        return $response;
    }

    abstract public function getIdmsaConnector(): IdmsaConnector;

    /**
     * @param string $account
     * @param string $m1
     * @param string $m2
     * @param string $c
     * @param bool   $rememberMe
     *
     * @throws RequestException
     * @throws FatalRequestException
     *
     * @return Response
     */
    public function complete(string $account, string $m1, string $m2, string $c, bool $rememberMe = false): Response
    {
        return $this->getIdmsaConnector()->send(
            new AuthorizeComplete(
                account: $account,
                m1: $m1,
                m2: $m2,
                c: $c,
                rememberMe: $rememberMe
            )
        );
    }

    /**
     * @throws RequestException
     * @throws FatalRequestException
     *
     * @return Response
     */
    public function sign(): Response
    {
        /**
         * @var Config $config
         */
        $config = $this->config();

        return $this->getIdmsaConnector()->send(
            new Signin(
                frameId: $this->buildUUid(),
                clientId: $config->getServiceKey(),
                redirectUri: $config->getApiUrl(),
                state: $this->buildUUid(),
            )
        );
    }

    /**
     * @param string $accountName
     * @param string $password
     * @param bool   $rememberMe
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @return Response
     */
    public function authorizeSing(string $accountName, string $password, bool $rememberMe = true): Response
    {
        return $this->getIdmsaConnector()->send(new AuthorizeSing($accountName, $password, $rememberMe));
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @return Response
     */
    public function auth(): Response
    {
        return $this->getIdmsaConnector()->send(new Auth());
    }

    /**
     * @param string $code
     *
     * @throws FatalRequestException
     * @throws RequestException
     * @throws VerificationCodeException
     *
     * @return Response
     */
    public function verifySecurityCode(string $code): Response
    {
        try {
            return $this->getIdmsaConnector()
                ->send(new VerifyTrustedDeviceSecurityCode($code));
        } catch (RequestException $e) {
            /**
             * @var Response $response
             */
            $response = $e->getResponse();

            if ($response->status() === 400) {
                throw new VerificationCodeException($response,$response->getFirstServiceError()?->getMessage() ?? '验证码错误', $response->status());
            }

            if ($response->status() === 412) {
                return $this->managePrivacyAccept();
            }

            throw $e;
        }
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @return Response
     */
    public function managePrivacyAccept(): Response
    {
        return $this->idmsaConnector->send(new AuthRepairComplete());
    }

    /**
     * @param string $id
     * @param string $code
     *
     * @throws FatalRequestException
     * @throws RequestException
     * @throws VerificationCodeException
     *
     * @return Response
     */
    public function verifyPhoneCode(string $id, string $code): Response
    {
        try {
            return $this->getIdmsaConnector()
                ->send(new VerifyPhoneSecurityCode($id, $code));
        } catch (RequestException $e) {
            /**
             * @var Response $response
             */
            $response = $e->getResponse();

            if ($response->status() === 400) {
                throw new VerificationCodeException($response,$response->getFirstServiceError()?->getMessage() ?? '验证码错误', $response->status());
            }

            if ($response->status() === 412) {
                return $this->managePrivacyAccept();
            }

            throw $e;
        }
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @return Response
     */
    public function sendSecurityCode(): Response
    {
        return $this->getIdmsaConnector()->send(new SendTrustedDeviceSecurityCode());
    }

    /**
     * @param int $id
     *
     * @throws FatalRequestException
     * @throws RequestException
     *
     * @return Response
     */
    public function sendPhoneSecurityCode(int $id): Response
    {
        return $this->getIdmsaConnector()->send(new SendPhoneSecurityCode($id));
    }
}
