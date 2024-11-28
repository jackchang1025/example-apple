<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Auth\AuthData;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasAuth
{
    protected ?AuthData $auth = null;

    public function withAuth(?AuthData $authData): static
    {
        $this->auth = $authData;

        return $this;
    }

    /**
     * @return AuthData
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function auth(): AuthData
    {
        return $this->auth ??= $this->refreshAuth();
    }

    /**
     * @return AuthData
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function refreshAuth(): AuthData
    {
        return $this->getClient()->getIdmsaConnector()->getAuthenticateResources()->auth();
    }
}
