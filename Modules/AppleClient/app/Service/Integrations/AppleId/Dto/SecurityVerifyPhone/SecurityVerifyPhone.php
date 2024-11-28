<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\SecurityVerifyPhone;

use Modules\AppleClient\Service\DataConstruct\Data;

class SecurityVerifyPhone extends Data
{
    public function __construct(
        public ?PhoneNumberVerification $phoneNumberVerification = null,
        public ?PhoneNumber $phoneNumber = null,
    ) {
    }
}
