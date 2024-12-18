<?php

namespace Modules\AppleClient\Service\Integrations\WebIcloud\Request;

use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\Devices\Devices;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class GetDevicesRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/setup/web/device/getDevices';
    }

    public function createDtoFromResponse(Response $response): Devices
    {
        return Devices::from($response->json());
    }
}
