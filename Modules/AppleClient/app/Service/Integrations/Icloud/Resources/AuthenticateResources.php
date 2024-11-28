<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Resources;

use Modules\AppleClient\Service\Integrations\Icloud\Request\AuthenticateRequest;
use Modules\AppleClient\Service\Response\Response;

class AuthenticateResources extends Resources
{
    /**
     * @param string $appleId
     * @param string $password
     * @param string|null $authCode
     * @return Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function authenticate(string $appleId, string $password, ?string $authCode = null): Response
    {
        return $this->getConnector()
            ->send(new AuthenticateRequest($appleId, $password, $authCode));
    }
}
