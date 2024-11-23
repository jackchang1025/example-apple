<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account\Person\ReachableAtOptions;

use Modules\AppleClient\Service\DataConstruct\Data;

class ContactInformationOptions extends Data
{
    public function __construct(
        public array $options,
    ) {
    }
}
