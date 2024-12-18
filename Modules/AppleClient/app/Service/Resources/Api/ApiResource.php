<?php

namespace Modules\AppleClient\Service\Resources\Api;

use Modules\AppleClient\Service\Resources\Api\Icloud\IcloudResource;
use Modules\AppleClient\Service\Resources\Resource;

class ApiResource extends Resource
{
    protected ?IcloudResource $icloudResource = null;

    public function getIcloudResource(): IcloudResource
    {
        return $this->icloudResource ??= app(IcloudResource::class, ['apiResource' => $this]);
    }
}



