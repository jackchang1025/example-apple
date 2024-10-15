<?php

namespace Modules\Phone\Service;

use App\Models\SecuritySetting;

class PhoneNumberFactory
{
    /**
     * @param string $phoneNumber
     * @param array|null $countryCode
     * @param int|null $phoneNumberFormat
     * @return PhoneService
     */
    public function create(
        string $phoneNumber,
        ?array $countryCode = null,
        ?int $phoneNumberFormat = null
    ): PhoneService {
        $phoneNumberFormat = $phoneNumberFormat ?? config('phone.format');

        $countryCode = $countryCode ?? [SecuritySetting::first()?->configuration['country_code']];

        return new PhoneService($phoneNumber, $countryCode, $phoneNumberFormat);
    }
}
