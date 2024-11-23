<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto;

use Modules\AppleClient\Service\DataConstruct\Data;


class AddPaymentDto extends Data
{

    public function __construct(
        public string $number,
        public string $expirationMonth,
        public string $expirationYear,
        public string $cw,
        public NameOnCardData $nameOnCard,
        public PhoneNumberData $phoneNumber,
        public BillingAddressData $billingAddress,
        public int $id,
    ) {
    }
}
