<?php

namespace App\Apple;

use App\Apple\Exception\VerificationCodeException;
use App\Apple\Integrations\Idmsa\IdmsaConnector;
use App\Apple\Integrations\Idmsa\Request\Appleauth\Auth;
use App\Apple\Integrations\Idmsa\Request\Appleauth\AuthorizeSing;
use App\Apple\Integrations\Idmsa\Request\Appleauth\AuthRepairComplete;
use App\Apple\Integrations\Idmsa\Request\Appleauth\SendPhoneSecurityCode;
use App\Apple\Integrations\Idmsa\Request\Appleauth\SendTrustedDeviceSecurityCode;
use App\Apple\Integrations\Idmsa\Request\Appleauth\Signin;
use App\Apple\Integrations\Idmsa\Request\Appleauth\VerifyPhoneSecurityCode;
use App\Apple\Integrations\Idmsa\Request\Appleauth\VerifyTrustedDeviceSecurityCode;
use App\Apple\Integrations\Response;

trait Idmsa
{
    abstract function getIdmsaConnector():IdmsaConnector;

    public function sign(): Response
    {
        return $this->getIdmsaConnector()->send(new Signin());
    }

    public function authorizeSing( string $accountName, string $password, bool $rememberMe = true): Response
    {
        return $this->getIdmsaConnector()->send(new AuthorizeSing($accountName, $password, $rememberMe));
    }

    public function auth(): Response
    {
        return $this->getIdmsaConnector()->send(new Auth());
    }

    /**
     * @param string $code
     * @return Response
     * @throws VerificationCodeException
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function verifySecurityCode(string $code): Response
    {
        $response = $this->getIdmsaConnector()->send(new VerifyTrustedDeviceSecurityCode($code));

        if ($response->status() === 412){

            $this->managePrivacyAccept();
        }else if ($response->status() === 400) {

            throw new VerificationCodeException($response->service_errors_first()?->getMessage(), $response->status());
        }

        return $response;
    }

    public function verifyPhoneCode(string $id,string $code): Response
    {
        $response = $this->getIdmsaConnector()->send(new VerifyPhoneSecurityCode($id,$code));

        if ($response->status() === 412){

            $this->managePrivacyAccept();

        }else if ($response->status() === 400) {

            throw new VerificationCodeException($response->service_errors_first()?->getMessage(), $response->status());
        }

        return $response;
    }

    public function sendSecurityCode(): Response
    {
        return $this->getIdmsaConnector()->send(new SendTrustedDeviceSecurityCode());
    }

    public function sendPhoneSecurityCode(int $id): Response
    {
        return $this->getIdmsaConnector()->send(new SendPhoneSecurityCode($id));
    }

    public function managePrivacyAccept(): Response
    {
        return $this->idmsaConnector->send(new AuthRepairComplete());
    }
}
