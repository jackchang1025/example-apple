<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Devices;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\Devices;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class DevicesRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/account/manage/security/devices';
    }

    public function createDtoFromResponse(Response $response): Devices
    {
        return Devices::from($response->json());
    }
}
