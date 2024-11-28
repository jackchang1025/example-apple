<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto;

use Spatie\LaravelData\Data;

class SignInInitData extends Data
{
    public function __construct(
        public string $salt,
        public string $b,
        public string $c,
        public int|string $iteration,
        public string $protocol
    ) {
    }
}
