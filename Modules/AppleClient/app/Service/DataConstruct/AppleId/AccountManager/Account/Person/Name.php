<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account\Person;


use Modules\AppleClient\Service\DataConstruct\Data;

class Name extends Data
{
    public function __construct(
        public bool $middleNameRequired,
        public string $firstName,
        public string $lastName,
    ) {
    }
}
