<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager;

use Modules\AppleClient\Service\DataConstruct\Data;

class UpdateNameData extends Data
{

    public function __construct(
        public string $firstName,
        public string $middleName,
        public string $lastName
    ) {
    }
}
