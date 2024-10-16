<?php

namespace Modules\AppleClient\Service\DataConstruct\Payment;

use Spatie\LaravelData\Data;

class StringData extends Data
{
    public function __construct(
        public string $value
    ) {
    }
}
