<?php

namespace Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto;

use Spatie\LaravelData\Data;

class SignInInitData extends Data
{

    public function __construct(public string $key, public string $value)
    {
    }
}
