<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto\SendVerificationCode;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;

class SendDeviceSecurityCodeData extends Data
{

    public function __construct(
        public PhoneNumberVerificationData $phoneNumberVerification,
        public string $aboutTwoFactorAuthenticationUrl,
        public SecurityCode $securityCode,
        public int $trustedDeviceCount,
        public ?string $otherTrustedDeviceClass = null,
    ) {
    }
}
