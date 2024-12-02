<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Resource;

use InvalidArgumentException;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\Auth;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SigninInit;
use Modules\AppleClient\Service\Response\Response;

class WebIcloudSignInResource extends BaseResource
{
    public function signInComplete(
        string $account,
        string $m1,
        string $m2,
        string $c,
        bool $rememberMe = false
    ): Response {
        return $this->getConnector()->send(
            new AuthorizeComplete(
                account: $account,
                m1: $m1,
                m2: $m2,
                c: $c,
                rememberMe: $rememberMe
            )
        );
    }

    public function signInInit(string $a, string $account): Response
    {
        $response = $this->getConnector()->send(new SigninInit($a, $account));

        if (empty($response->json('salt'))) {
            throw new InvalidArgumentException("salt IS EMPTY");
        }

        if (empty($response->json('b'))) {
            throw new InvalidArgumentException("b IS EMPTY");
        }

        if (empty($response->json('c'))) {
            throw new InvalidArgumentException("c IS EMPTY");
        }

        if (empty($response->json('iteration'))) {
            throw new InvalidArgumentException("iteration IS EMPTY");
        }

        if (empty($response->json('protocol'))) {
            throw new InvalidArgumentException("protocol IS EMPTY");
        }

        return $response;
    }

    public function auth()
    {
        return $this->getConnector()->send(new Auth());
    }
}
