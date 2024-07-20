<?php

namespace App\Apple\Service\PhoneNumber;

use libphonenumber\PhoneNumberFormat;

class PhoneNumberFactory
{
    /**
     * @param string $phoneNumber
     * @param string|null $countryCode
     * @param int $phoneNumberFormat
     * @return PhoneNumberService
     * @throws \InvalidArgumentException|\libphonenumber\NumberParseException 如果电话号码无效
     */
    public function createPhoneNumberService(
        string $phoneNumber,
        ?string $countryCode = null,
        int $phoneNumberFormat = PhoneNumberFormat::E164
    ): PhoneNumberService
    {
        return new PhoneNumberService($phoneNumber, $countryCode, $phoneNumberFormat);
    }
}
