<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Resources;

use Modules\AppleClient\Service\Exception\AppleRequestException\LoginRequestException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\LoginDelegatesRequest;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class Resources
{
    public function __construct(protected IcloudConnector $connector)
    {
        //
    }

    public function getConnector(): IcloudConnector
    {
        return $this->connector;
    }

    /**
     * @param string $appleId
     * @param string $password
     * @param string $authCode
     * @param string $clientId
     * @param string $protocolVersion
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException|LoginRequestException|VerificationCodeException
     */
    public function loginDelegatesRequest(
        string $appleId,
        string $password,
        string $authCode = '',
        string $clientId = '67BDADCA-6E66-7ED7-A01A-5EB3C5D95CE3',
        string $protocolVersion = '4'
    ): Response {
        return $this->getConnector()
            ->send(new LoginDelegatesRequest($appleId, $password, $authCode, $clientId, $protocolVersion));
    }
}
