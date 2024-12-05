<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Devices\Device;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class GetDevicesRequest  extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/setup/web/device/getDevices';
    }

    public function createDtoFromResponse(Response $response): Device
    {
        return Device::from($response->json());
    }
}
