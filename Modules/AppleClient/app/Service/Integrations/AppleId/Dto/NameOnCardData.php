<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto;

use Spatie\LaravelData\Data;

class NameOnCardData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {
    }
}
