<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto;

use Spatie\LaravelData\Data;

class VerifyCVVRequestDto extends Data
{
    public function __construct(
        public string $securityCode,
        public string $creditCardId,
        public string $verificationType,
        public ?string $creditCardLastFourDigits = null,
        public ?string $partnerLogin = null,
        public ?string $smsSessionID = null,
        public string $billingType = 'Card',
    ) {
    }
}
