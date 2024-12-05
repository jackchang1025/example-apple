<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Resource;

use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Request\SignIn\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete as SignInCompleteResponse;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInInit;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SignInCompleteRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SigninInitRequest;

class WebIcloudSignInResource extends BaseResource
{

    public function signInComplete(SignInComplete $data): SignInCompleteResponse
    {
        return $this->getConnector()->send(new SignInCompleteRequest($data))->dto();
    }

    public function signInInit(string $a, string $account): SignInInit
    {
        return $this->getConnector()->send(new SigninInitRequest($a, $account))->dto();
    }

    public function auth()
    {
        return $this->getConnector()->send(new AuthRequest());
    }
}
