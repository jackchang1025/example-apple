<?php

namespace Modules\AppleClient\Service\DataConstruct\Payment;

use Spatie\LaravelData\Data;

class StateProvince extends Data
{
    public function __construct(
        public string $code,
        public string $name
    ) {
    }
}
