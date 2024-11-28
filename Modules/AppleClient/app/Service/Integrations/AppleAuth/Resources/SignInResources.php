<?php

namespace Modules\AppleClient\Service\Integrations\AppleAuth\Resources;

use Modules\AppleClient\Service\Integrations\AppleAuth\Dto\CompleteData;
use Modules\AppleClient\Service\Integrations\AppleAuth\Dto\InitData;
use Modules\AppleClient\Service\Integrations\AppleAuth\Request\Complete;
use Modules\AppleClient\Service\Integrations\AppleAuth\Request\Init;
use Modules\AppleClient\Service\Integrations\BaseResource;

class SignInResources extends BaseResource
{
    /**
     * @param string $account
     *
     * @return InitData
     * @throws \Saloon\Exceptions\Request\RequestException|\JsonException
     *
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function signInInit(string $account): InitData
    {
        return $this->getConnector()
            ->send(new Init($account))
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
     * @return CompleteData
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
    ): CompleteData {
        return $this->getConnector()->send(
            new Complete(
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
