<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Payment;

use Modules\AppleClient\Service\DataConstruct\Data;

class StringData extends Data
{
    public function __construct(
        public string $value
    ) {
    }
}
