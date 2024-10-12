<?php

namespace App\Apple\Trait;

use App\Apple\Integrations\Idmsa\Config;

trait HasAppleConfig
{
    protected ?Config $appleConfig = null;

    public function appleConfig(): Config
    {
        return $this->appleConfig ??= new Config();
    }
}