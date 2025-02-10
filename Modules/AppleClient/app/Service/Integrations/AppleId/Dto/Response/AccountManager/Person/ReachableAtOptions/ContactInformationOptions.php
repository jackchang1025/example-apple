<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Person\ReachableAtOptions;

use Modules\AppleClient\Service\DataConstruct\Data;

class ContactInformationOptions extends Data
{
    public function __construct(
        public array $options = [],
    ) {
    }
}
