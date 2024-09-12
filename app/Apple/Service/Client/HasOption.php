<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\Option;

trait HasOption
{
    protected ?Option $option = null;

    public function pushOption(string $key, mixed $value): static
    {
        $this->getOption()->push($key, $value);

        return $this;
    }

    public function getOption(): Option
    {
        return $this->option ??= new Option($this->configureIpAddress());
    }

    public function setOption(Option $option): static
    {
        $this->option = $option;

        return $this;
    }

    protected function configureIpAddress(): array
    {
        if ($this->isIpaddressEnabled()) {
            $ipaddress = $this->user->get('ipaddress');
            if ($ipaddress !== null && $ipaddress->isChain()) {
                return [
                    'city'     => $ipaddress->cityCode(),
                    'province' => $ipaddress->proCode(),
                ];
            }
        }

        return [];
    }

}
