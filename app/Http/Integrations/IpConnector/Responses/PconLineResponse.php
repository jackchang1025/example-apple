<?php

namespace App\Http\Integrations\IpConnector\Responses;

class PconLineResponse extends IpResponse
{
    public function getCity(): ?string
    {
        return $this->get('city');
    }

    public function getAddr(): ?string
    {
        return $this->get('addr');
    }

    public function getIp(): ?string
    {
        return $this->get('ip');
    }

    public function cityCode(): ?string
    {
        return $this->get('cityCode');
    }

    public function proCode(): ?string
    {
        return $this->get('proCode');
    }
}
