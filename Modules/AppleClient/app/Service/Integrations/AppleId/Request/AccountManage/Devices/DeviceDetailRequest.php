<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Devices;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\DeviceDetail;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class DeviceDetailRequest extends Request
{

    protected Method $method = Method::GET;

    /**
     * @param string $deviceId
     */
    public function __construct(public string $deviceId)
    {
    }

    public function createDtoFromResponse(Response $response): DeviceDetail
    {
        return DeviceDetail::from($response->json());
    }

    public function resolveEndpoint(): string
    {
        return "/account/manage/security/devices/{$this->deviceId}";
    }
}
