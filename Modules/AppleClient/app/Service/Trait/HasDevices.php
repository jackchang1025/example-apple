<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\DataConstruct\Device\Devices;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

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
}
