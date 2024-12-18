<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\DeviceDetail;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\Devices;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Devices\DeviceDetailRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Devices\DevicesRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class SecurityDevicesResources extends BaseResource
{
    /**
     * @param string $deviceId
     * @return DeviceDetail
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function deviceDetail(string $deviceId): DeviceDetail
    {
        return $this->getConnector()
            ->send(new DeviceDetailRequest($deviceId))
            ->dto();
    }

    /**
     * @return Devices
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function devices(): Devices
    {
        return $this->getConnector()
            ->send(new DevicesRequest())
            ->dto();
    }
}
