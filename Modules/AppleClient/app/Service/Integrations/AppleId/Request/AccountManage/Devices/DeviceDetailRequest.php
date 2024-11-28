<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Device\DeviceDetailData;
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

    public function createDtoFromResponse(Response $response): DeviceDetailData
    {
        return DeviceDetailData::from($response->json());
    }

    public function resolveEndpoint(): string
    {
        return "/account/manage/security/devices/{$this->deviceId}";
    }
}
