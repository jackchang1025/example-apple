<?php

namespace Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Resources;

use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\SignInCompleteData;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\SignInInitData;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInCompleteRequest;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInInitRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;

class AuthenticationResource extends BaseResource
{
    /**
     * @param string $account
     *
     * @return SignInInitData
     * @throws \Saloon\Exceptions\Request\RequestException|\JsonException
     *
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function signInInit(string $account): SignInInitData
    {
        return $this->getConnector()
            ->send(new SignInInitRequest($account))
            ->dto();
    }

    /**
     * @param string $key
     * @param string $salt
     * @param string $b
     * @param string $c
     * @param string $password
     * @param string $iteration
     * @param string $protocol
     *
     * @return SignInCompleteData
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException|\JsonException
     *
     */
    public function signInComplete(
        string $key,
        string $salt,
        string $b,
        string $c,
        string $password,
        string $iteration,
        string $protocol
    ): SignInCompleteData
    {
        return $this->getConnector()->send(
            new SignInCompleteRequest(
                key: $key,
                b: $b,
                salt: $salt,
                c: $c,
                password: $password,
                iteration: $iteration,
                protocol: $protocol
            )
        )->dto();
    }
}
