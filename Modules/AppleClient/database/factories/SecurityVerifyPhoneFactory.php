<?php

namespace Modules\AppleClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\PhoneNumberVerification;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone;

class SecurityVerifyPhoneFactory extends Factory
{

    public function definition(): array
    {
        return [
            'phoneNumberVerification' => $this->fakePhoneNumberVerification(),
        ];
    }

    protected function fakePhoneNumberVerification(): PhoneNumberVerification
    {
        $phoneNumber = $this->fakePhoneNumber();

        return new PhoneNumberVerification(
            phoneNumber: $phoneNumber,
            securityCode: $this->fakeSecurityCode(),
            mode: $this->faker->randomElement(['sms', 'voice']),
            type: 'verification',
            authenticationType: 'hsa2',
            showAutoVerificationUI: $this->faker->boolean,
            countryCode: $phoneNumber->countryCode,
            countryDialCode: $phoneNumber->countryDialCode,
            number: $phoneNumber->number,
            keepUsing: $this->faker->boolean,
            changePhoneNumber: $this->faker->boolean,
            simSwapPhoneNumber: $this->faker->boolean,
            addDifferent: $this->faker->boolean
        );
    }

    protected function fakePhoneNumber(): PhoneNumber
    {
        $countryCode     = $this->faker->randomElement(['HK', 'US', 'CN']);
        $countryDialCode = $this->getCountryDialCode($countryCode);
        $number          = $this->faker->numerify('########');
        $lastTwoDigits   = substr($number, -2);

        return new PhoneNumber(
            deviceType: false,
            nonFTEU: $this->faker->boolean,
            iMessageType: false,
            pacType: false,
            complianceType: false,
            appleComplianceType: false,
            numberWithDialCode: "+{$countryDialCode} {$number}",
            numberWithDialCodeAndExtension: "+{$countryDialCode} {$number}",
            rawNumber: $number,
            fullNumberWithCountryPrefix: "+{$countryDialCode} {$number}",
            sameAsAppleID: false,
            verified: false,
            countryCode: $countryCode,
            pushMode: 'sms',
            countryDialCode: $countryDialCode,
            number: $number,
            vetted: false,
            createDate: $this->faker->dateTimeThisYear()->format('m/d/Y h:i:s A'),
            updateDate: $this->faker->dateTimeThisYear()->format('m/d/Y h:i:s A'),
            rawNumberWithDialCode: "+{$countryDialCode}{$number}",
            pending: $this->faker->boolean,
            lastTwoDigits: $lastTwoDigits,
            loginHandle: false,
            countryCodeAsString: $countryCode,
            obfuscatedNumber: '•••• ••'.$lastTwoDigits,
            name: "+{$countryDialCode} {$number}",
            id: $this->faker->unique()->numberBetween(10000, 99999)
        );
    }

    protected function fakeSecurityCode(): SecurityCode
    {
        return new SecurityCode(
            tooManyCodesSent: $this->faker->boolean(10),
            tooManyCodesValidated: $this->faker->boolean(10),
            securityCodeLocked: $this->faker->boolean(5),
            securityCodeCooldown: $this->faker->boolean(20),
            length: 6
        );
    }

    protected function getCountryDialCode(string $countryCode): string
    {
        return [
                   'HK' => '852',
                   'US' => '1',
                   'CN' => '86',
               ][$countryCode] ?? '1';
    }

    public function makeOne($attributes = []): SecurityVerifyPhone
    {
        return SecurityVerifyPhone::from($this->definition());
    }

    public function makePhoneNumber(): SecurityVerifyPhone
    {
        return SecurityVerifyPhone::from([
            'phoneNumber' => $this->fakePhoneNumber(),
        ]);
    }

    public function makes(int $count = 1): array
    {
        return array_map(fn() => $this->makeOne(), range(1, $count));
    }
}

