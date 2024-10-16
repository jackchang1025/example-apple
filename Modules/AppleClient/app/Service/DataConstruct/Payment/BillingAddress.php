<?php

namespace Modules\AppleClient\Service\DataConstruct\Payment;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class BillingAddress extends Data
{
    public function __construct(
        public bool $defaultAddress,
        public bool $stateProvinceInvalid,
        public bool $japanese,
        public bool $korean,
        public array $formattedAddress,
        public bool $primary,
        public bool $shipping,
        public string $countryCode,
        public string $countryName,
        #[DataCollectionOf(StateProvince::class)]
        public DataCollection $stateProvinces,
        public string $stateProvinceCode,
        public string $line1,
        public string $line2,
        public string $city,
        public string $stateProvinceName,
        public string $postalCode,
        public array $stateProvince,
        public bool $usa,
        public bool $canada,
        public bool $preferred,
        public string $fullAddress,
        public string $id,
    ) {
    }
}
