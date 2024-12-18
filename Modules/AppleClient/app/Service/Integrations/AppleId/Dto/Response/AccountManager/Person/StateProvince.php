<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Person;

use Modules\AppleClient\Service\DataConstruct\Data;

class StateProvince extends Data
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }
}
