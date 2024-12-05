<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Authenticate;
use Modules\AppleClient\Service\DataConstruct\Data;

class AppleAccountInfo extends Data
{
    public function __construct(
        public string $dsid,
        public int $dsPrsID,
    ) {
    }
}
