<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Authenticate;

use Modules\AppleClient\Service\DataConstruct\Data;

class Tokens extends Data
{
    public function __construct(
        public string $mmeAuthToken,
    ) {
    }
}
