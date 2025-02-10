<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Person;

use Modules\AppleClient\Service\DataConstruct\Data;

class LoginHandles extends Data
{
    public function __construct(
        public array $phoneLoginHandles = [],
        public array $emailLoginHandles = [],
    ) {
    }
}
