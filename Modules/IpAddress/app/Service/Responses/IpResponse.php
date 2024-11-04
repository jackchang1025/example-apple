<?php

namespace Modules\IpAddress\Service\Responses;

use Saloon\Repositories\ArrayStore;

class IpResponse extends ArrayStore
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

    public function getCityCode(): ?string
    {
        return $this->get('city_code');
    }

    public function getProCode(): ?string
    {
        return $this->get('pro_code');
    }

    public function isChain(): bool
    {
        return $this->get('is_chain',false);
    }
}
