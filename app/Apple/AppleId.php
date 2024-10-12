<?php

namespace App\Apple;

use App\Apple\Integrations\AppleId\AppleIdConnector;
use App\Apple\Integrations\AppleId\Request\AccountManage\SecurityVerifyPhone;
use App\Apple\Integrations\AppleId\Request\AccountManage\SecurityVerifyPhoneSecurityCode;
use App\Apple\Integrations\AppleId\Request\AccountManage\Token;
use App\Apple\Integrations\AppleId\Request\AuthenticatePassword;
use App\Apple\Integrations\AppleId\Request\Bootstrap;
use App\Apple\Integrations\Response;

trait AppleId
{
    abstract function getAppleIdConnector():AppleIdConnector;

    /**
     * @return Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function bootstrap(): Response
    {
        return $this->getAppleIdConnector()->send(new Bootstrap());
    }

    /**
     * @param string $password
     * @return Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function authenticatePassword(string $password): Response
    {
        return $this->getAppleIdConnector()->send(new AuthenticatePassword($password));
    }

    /**
     * @return Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function token(): Response
    {
        return $this->getAppleIdConnector()->send(new Token());
    }

    /**
     * @param string $countryCode
     * @param string $phoneNumber
     * @param string $countryDialCode
     * @param bool $nonFTEU
     * @return Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function securityVerifyPhone( string $countryCode,  string $phoneNumber,  string $countryDialCode,  bool $nonFTEU = true): Response
    {
        return $this->getAppleIdConnector()->send(new SecurityVerifyPhone($countryCode, $phoneNumber, $countryDialCode, $nonFTEU));
    }

    /**
     * @param int $id
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param string $code
     * @return Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function securityVerifyPhoneSecurityCode( int $id, string $phoneNumber, string $countryCode, string $countryDialCode, string $code): Response
    {
        return$this->getAppleIdConnector()->send(new SecurityVerifyPhoneSecurityCode($id, $phoneNumber, $countryCode, $countryDialCode, $code));
    }
}
