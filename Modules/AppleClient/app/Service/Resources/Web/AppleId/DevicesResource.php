<?php

namespace Modules\AppleClient\Service\Resources\Web\AppleId;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\Device;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\DeviceDetail;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\Devices;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

class DevicesResource
{

    public function __construct(protected AppleIdResource $appleIdResource)
    {

    }

    public function getAppleIdResource(): AppleIdResource
    {
        return $this->appleIdResource;
    }

    /**
     * @return Devices
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getDevices(): Devices
    {
        return $this->getAppleIdResource()->getAppleIdConnector()->getSecurityDevicesResources()->devices();
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getDevicesDetails(): DataCollection
    {
        return $this->getDevices()->devices
            ->map(function (Device $device) {

                if ($device->deviceDetail) {
                    return $device;
                }

                $device->deviceDetail = $this->getDevicesDetail($device->deviceId);

                return $device;
            });
    }

    /**
     * @param string $paymentId
     * @return DeviceDetail
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getDevicesDetail(string $paymentId): DeviceDetail
    {
        return $this->getAppleIdResource()->getAppleIdConnector()->getSecurityDevicesResources()->deviceDetail(
            $paymentId
        );
    }
}
