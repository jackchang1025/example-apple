<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\UpdateName;

use Modules\AppleClient\Service\DataConstruct\Data;

class UpdateName extends Data
{

    public function __construct(
        public string $firstName,
        public string $middleName,
        public string $lastName
    ) {
    }
}
