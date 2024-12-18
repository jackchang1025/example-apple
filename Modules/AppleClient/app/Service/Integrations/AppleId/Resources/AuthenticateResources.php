<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Token\Token;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\ValidatePassword\ValidatePassword;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\TokenRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AuthenticatePasswordRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class AuthenticateResources extends BaseResource
{
    /**
     * @param string $password
     *
     * @return ValidatePassword
     * @throws FatalRequestException
     *
     * @throws RequestException
     */
    public function authenticatePassword(string $password): ValidatePassword
    {
        return $this->getConnector()
            ->send(new AuthenticatePasswordRequest($password))->dto();
    }

    /**
     * @return Token
     * @throws FatalRequestException
     *
     * @throws RequestException
     */
    public function token(): Token
    {
        return $this->getConnector()->send(new TokenRequest())->dto();
    }
}
