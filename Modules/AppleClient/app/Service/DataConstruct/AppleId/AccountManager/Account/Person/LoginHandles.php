<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account\Person;

use Modules\AppleClient\Service\DataConstruct\Data;

class LoginHandles extends Data
{
    public function __construct(
        public array $phoneLoginHandles,
        public array $emailLoginHandles,
    ) {
    }
}
