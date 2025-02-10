<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Person;


use Modules\AppleClient\Service\DataConstruct\Data;

class Name extends Data
{
    public function __construct(
        public bool $middleNameRequired = false,
        public ?string $firstName = null,
        public ?string $lastName = null,
    ) {
    }
}
