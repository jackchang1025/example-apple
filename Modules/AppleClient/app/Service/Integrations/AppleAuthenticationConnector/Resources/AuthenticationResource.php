<?php

namespace Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Resources;

use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Request\SignInComplete as SignInCompleteRequestData;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Response\SignInComplete;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Response\SignInInit;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInCompleteRequest;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInInitRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class AuthenticationResource extends BaseResource
{
    /**
     * @param string $account
     *
     * @return SignInInit
     * @throws \Saloon\Exceptions\Request\RequestException|\JsonException
     *
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     */
    public function signInInit(string $account): SignInInit
    {
        return $this->getConnector()
            ->send(new SignInInitRequest($account))
            ->dto();
    }

    /**
     * @param SignInCompleteRequestData $data
     * @return SignInComplete
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function signInComplete(SignInCompleteRequestData $data): SignInComplete
    {
        return $this->getConnector()->send(
            new SignInCompleteRequest($data)
        )->dto();
    }
}
