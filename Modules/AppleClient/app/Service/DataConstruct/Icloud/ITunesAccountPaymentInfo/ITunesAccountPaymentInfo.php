<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\ITunesAccountPaymentInfo;

use Modules\AppleClient\Service\DataConstruct\Data;
use Spatie\LaravelData\Attributes\MapName;

class ITunesAccountPaymentInfo extends Data
{

    public function __construct(
        #[MapName('status-message')]
        public string $statusMessage,
        public int $status,
        public ?string $userAction = null,
        public ?string $billingType = null,
        public ?string $creditCardImageUrl = null,
        public ?string $creditCardLastFourDigits = null,
        public ?string $verificationType = null,
        public ?string $creditCardId = null,
        public ?string $creditCardType = null,

    ) {
    }
}
