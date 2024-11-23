<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto;

use Spatie\LaravelData\Data;

class PhoneNumberData extends Data
{
    public function __construct(
        public string $areaCode,
        public string $number,
        public string $countryCode,
    ) {
    }
}
