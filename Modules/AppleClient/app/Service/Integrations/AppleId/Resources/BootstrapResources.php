<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Integrations\AppleId\Request\Bootstrap;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class BootstrapResources extends BaseResource
{
    /**
     * @return Response
     * @throws FatalRequestException
     *
     * @throws RequestException
     */
    public function bootstrap(): Response
    {
        return $this->getConnector()->send(new Bootstrap());
    }
}
