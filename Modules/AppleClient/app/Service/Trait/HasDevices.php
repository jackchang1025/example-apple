<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\DataConstruct\Device\Device;
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
        return $this->devices ??= $this->securityDevices();
    }

    /**
     * @return DataCollection
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function fetchDevices(): \Spatie\LaravelData\DataCollection
    {
        return $this->getDevices()->devices
            ->map(function (Device $device) {
                return $device->updateOrCreate($this->getAccount()->id);
            });
    }

    /**
     * @return Devices
     * @throws FatalRequestException
     * @throws RequestException|\JsonException
     */
    protected function securityDevices(): Devices
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
        $this->devices = $this->securityDevices();

        return $this->devices;
    }

    public function withDevices(?Devices $devices = null): static
    {
        $this->devices = $devices;

        return $this;
    }
}
