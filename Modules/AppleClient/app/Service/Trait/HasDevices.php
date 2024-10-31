<?php

namespace Modules\AppleClient\Service\Trait;

use JsonException;
use Modules\AppleClient\Service\DataConstruct\Device\Device;
use Modules\AppleClient\Service\DataConstruct\Device\DeviceDetail;
use Modules\AppleClient\Service\DataConstruct\Device\Devices;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

trait HasDevices
{
    protected ?Devices $devices = null;


    /**
     * @return Devices
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function getDevices(): Devices
    {
        return $this->devices ??= $this->fetchDevices();
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws JsonException
     * @throws RequestException
     */
    public function getDevicesDetails(): \Spatie\LaravelData\DataCollection
    {
        return $this->getDevices()->devices
            ->map(function (Device $device) {

                if ($device->deviceDetail) {
                    return $device;
                }

                $device->deviceDetail = $this->fetchDevicesDetail($device->deviceId);

                return $device;
            });
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function updateOrCreateDevices(): \Spatie\LaravelData\DataCollection
    {
        return $this->getDevicesDetails()
            ->map(function (Device $device) {

                return $device->deviceDetail?->updateOrCreate($this->getAccount()->id);
            });
    }

    /**
     * @return Devices
     * @throws FatalRequestException
     * @throws RequestException|\JsonException
     */
    protected function fetchDevices(): Devices
    {
        return Devices::from($this->client->securityDevices()->json());
    }

    /**
     * 刷新设备列表
     * @return Devices
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function refreshDevices(): Devices
    {
        $this->devices = $this->fetchDevices();

        return $this->devices;
    }

    public function withDevices(?Devices $devices = null): static
    {
        $this->devices = $devices;

        return $this;
    }

    /**
     * @param string $paymentId
     * @return DeviceDetail
     * @throws FatalRequestException
     * @throws JsonException
     * @throws RequestException
     */
    public function fetchDevicesDetail(string $paymentId): DeviceDetail
    {
        return DeviceDetail::fromResponse(
            $this->getClient()->deviceDetail($paymentId)
        );
    }
}
