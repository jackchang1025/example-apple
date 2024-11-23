<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account\Person;

use Modules\AppleClient\Service\DataConstruct\Data;

class StateProvince extends Data
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }
}
