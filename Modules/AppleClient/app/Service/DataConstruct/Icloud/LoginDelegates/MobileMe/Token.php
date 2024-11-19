<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\MobileMe;

use Modules\AppleClient\Service\DataConstruct\Data;

class Token extends Data
{
    public function __construct(
        public string $mmeFMFAppToken,
        public string $mapsToken,
        public string $mmeFMIPToken,
        public string $cloudKitToken,
        public string $mmeAuthToken,
        public string $mmeFMFToken,
    ) {
    }
}
