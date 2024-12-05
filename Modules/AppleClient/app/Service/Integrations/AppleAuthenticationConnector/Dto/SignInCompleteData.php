<?php

namespace Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto;

use Spatie\LaravelData\Data;

class SignInCompleteData extends Data
{

    public function __construct(
        public string $M1,
        public string $M2,
        public string $c,
    ) {
    }

}
