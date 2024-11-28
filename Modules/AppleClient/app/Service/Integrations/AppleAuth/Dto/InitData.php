<?php

namespace Modules\AppleClient\Service\Integrations\AppleAuth\Dto;

use Spatie\LaravelData\Data;

class InitData extends Data
{

    public function __construct(public string $key, public string $value)
    {
    }
}
