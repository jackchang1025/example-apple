<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account\Person;

use Modules\AppleClient\Service\DataConstruct\Data;

class DefaultShippingAddress extends Data
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
        public string $label,
        public string $company,
        public string $line1,
        public string $line2,
        public string $line3,
        public string $city,
        public StateProvince $stateProvince,
        public string $postalCode,
        public string $countryName,
        public string $county,
        public string $suburb,
        public string $recipientFirstName,
        public string $recipientLastName,
        public array $stateProvinces,
        public bool $usa,
        public bool $canada,
        public string $fullAddress,
        public bool $preferred,
        public string $id,
        public string $type,
    ) {
    }
}
