<?php

namespace Modules\AppleClient\Service\Integrations\AppleAuth\Dto;

use Spatie\LaravelData\Data;

class CompleteData extends Data
{

    public function __construct(
        public string $M1,
        public string $M2,
        public string $c,
    ) {
    }

}
