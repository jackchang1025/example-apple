<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Request\AddPayment;

use Spatie\LaravelData\Data;

class BillingAddressData extends Data
{
    public function __construct(
        public string $id,
        public string $line1,
        public string $line2,
        public string $line3,
        public string $suburb,
        public string $county,
        public string $city,
        public string $countryCode,
        public string $postalCode,
        public string $stateProvinceName,
    ) {
    }
}
