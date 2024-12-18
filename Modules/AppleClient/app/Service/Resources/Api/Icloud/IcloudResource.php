<?php

namespace Modules\AppleClient\Service\Resources\Api\Icloud;

use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Resources\Api\ApiResource;
use Modules\AppleClient\Service\Resources\Api\Authentication\AuthenticationResource;
use Saloon\Http\Auth\BasicAuthenticator;

class IcloudResource
{
    protected ?FamilyResources $familyResources = null;
    protected ?AuthenticationResource $authenticationResource = null;

    protected ?IcloudConnector $icloudConnector = null;

    protected ?BasicAuthenticator $authenticator = null;

    public function __construct(protected ApiResource $resource)
    {
    }

    public function getResource(): ApiResource
    {
        return $this->resource;
    }

    public function getAuthenticator(): ?BasicAuthenticator
    {
        return $this->authenticator;
    }

    public function getIcloudConnector(): IcloudConnector
    {
        return $this->icloudConnector ??= new IcloudConnector(
            $this->getResource()->getApple(),
            $this->getAuthenticator()
        );
    }

    public function getAuthenticationResource(): AuthenticationResource
    {
        return $this->authenticationResource ??= new AuthenticationResource($this);
    }

    public function getFamilyResources(): FamilyResources
    {
        return $this->familyResources ??= new FamilyResources($this);
    }
}
