<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager;

use Modules\AppleClient\Service\DataConstruct\Data;

class VettingStatus extends Data
{
    public function __construct(
        public string $type,
        public bool $vetted,
        public bool $notVetted,
        public bool $pending,
    ) {
    }
}
