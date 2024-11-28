<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\Authenticate;

use Modules\AppleClient\Service\DataConstruct\Data;

class Tokens extends Data
{
    public function __construct(
        public string $mmeAuthToken,
    ) {
    }
}
