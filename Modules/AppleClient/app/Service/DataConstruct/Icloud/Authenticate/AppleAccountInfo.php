<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\Authenticate;

use Modules\AppleClient\Service\DataConstruct\Data;

class AppleAccountInfo extends Data
{
    public function __construct(
        public string $dsid,
        public int $dsPrsID,
    ) {
    }
}
