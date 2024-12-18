<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Resources;

use Modules\AppleClient\Service\Exception\AppleRequestException\LoginRequestException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Authenticate\Authenticate;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\LoginDelegates\LoginDelegates;
use Modules\AppleClient\Service\Integrations\Icloud\Request\AuthenticateRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class AuthenticateResources extends BaseResource
{
    /**
     * @param string $appleId
     * @param string $password
     * @param string|null $authCode
     * @return Authenticate
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function authenticate(string $appleId, string $password, ?string $authCode = null): Authenticate
    {
        return $this->getConnector()
            ->send(new AuthenticateRequest($appleId, $password, $authCode))
            ->dto();
    }

    /**
     * @param string $appleId
     * @param string $password
     * @param string $authCode
     * @param string $clientId
     * @param string $protocolVersion
     * @return LoginDelegates
     * @throws FatalRequestException
     * @throws RequestException|LoginRequestException|VerificationCodeException
     */
    public function loginDelegatesRequest(
        string $appleId,
        string $password,
        string $authCode = '',
        string $clientId = '67BDADCA-6E66-7ED7-A01A-5EB3C5D95CE3',
        string $protocolVersion = '4'
    ): LoginDelegates
    {
        return $this->getConnector()
            ->send(new LoginDelegatesRequestTest($appleId, $password, $authCode, $clientId, $protocolVersion))
            ->dto();
    }
}
