<?php

namespace Modules\AppleClient\Service\Resources\Web;

use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\AppleAuthenticationConnector;
use Modules\AppleClient\Service\Resources\Resource;
use Modules\AppleClient\Service\Resources\Web\AppleId\AppleIdResource;
use Modules\AppleClient\Service\Resources\Web\Idmsa\IdmsaResource;
use Modules\AppleClient\Service\Resources\Web\Icloud\IcloudResource;

class WebResource extends Resource
{
    protected ?AppleAuthenticationConnector $appleAuthenticationConnector = null;

    protected ?AppleIdResource $appleIdResource = null;

    protected ?IdmsaResource $idmsaResource = null;

    protected ?IcloudResource $icloudResource = null;

    public function getAppleAuthenticationConnector(): AppleAuthenticationConnector
    {
        return $this->appleAuthenticationConnector ??= new AppleAuthenticationConnector(
            $this->getApple(),
            $this->getApple()->getConfig()->get('apple_auth.url')
        );
    }

    public function getAppleIdResource(): AppleIdResource
    {
        return $this->appleIdResource ??= app(AppleIdResource::class, ['webResource' => $this]);
    }

    public function getIdmsaResource(): IdmsaResource
    {
        return $this->idmsaResource ??= app(IdmsaResource::class, ['webResource' => $this]);
    }

    public function getIcloudResource(): IcloudResource
    {
        return $this->icloudResource ??= app(IcloudResource::class, ['webResource' => $this]);
    }

}



