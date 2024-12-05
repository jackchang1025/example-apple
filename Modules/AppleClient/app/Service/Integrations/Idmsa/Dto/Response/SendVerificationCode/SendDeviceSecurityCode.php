<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;

class SendDeviceSecurityCode extends Data
{

    public function __construct(
        public PhoneNumberVerification $phoneNumberVerification,
        public string $aboutTwoFactorAuthenticationUrl,
        public SecurityCode $securityCode,
        public int $trustedDeviceCount,
        public ?string $otherTrustedDeviceClass = null,
    ) {
    }
}
