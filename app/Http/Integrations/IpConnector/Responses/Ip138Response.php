<?php

namespace App\Http\Integrations\IpConnector\Responses;

class Ip138Response extends IpResponse
{
    public function getCity(): ?string
    {
        return $this->get('data')[2] ?? null;
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
        // TODO: Implement cityCode() method.
    }

    public function proCode(): ?string
    {
        // TODO: Implement proCode() method.
    }

    public function isChain(): bool
    {
        // TODO: Implement isChain() method.
    }
}
