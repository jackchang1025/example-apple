<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Device\DeviceDetailData;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Device\DevicesData;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\DeviceDetailRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\DevicesRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class SecurityDevicesResources extends BaseResource
{
    /**
     * @param string $deviceId
     * @return DeviceDetailData
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function deviceDetail(string $deviceId): DeviceDetailData
    {
        return $this->getConnector()
            ->send(new DeviceDetailRequest($deviceId))
            ->dto();
    }

    /**
     * @return DevicesData
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function devices(): DevicesData
    {
        return $this->getConnector()
            ->send(new DevicesRequest())
            ->dto();
    }
}
