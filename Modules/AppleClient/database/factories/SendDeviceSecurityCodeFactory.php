<?php

namespace Modules\AppleClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\PhoneNumberVerification;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendDeviceSecurityCode;
use Spatie\LaravelData\DataCollection;

class SendDeviceSecurityCodeFactory extends Factory
{

    public function definition(): array
    {
        return [
            'phoneNumberVerification'         => $this->fakePhoneNumberVerification(),
            'otherTrustedDeviceClass'         => $this->faker->randomElement(['iPhone', 'iPad', 'Mac', 'Apple Watch']),
            'aboutTwoFactorAuthenticationUrl' => $this->faker->url,
            'securityCode'                    => $this->fakeSecurityCode(),
            'trustedDeviceCount'              => $this->faker->numberBetween(1, 5),
        ];
    }

    protected function fakePhoneNumberVerification(): PhoneNumberVerification
    {
        return new PhoneNumberVerification(
            aboutTwoFactorAuthenticationUrl: $this->faker->url,
            authenticationType: 'hsa2',
            autoVerified: $this->faker->boolean,
            hideSendSMSCodeOption: $this->faker->boolean,
            hsa2Account: $this->faker->boolean,
            managedAccount: $this->faker->boolean,
            restrictedAccount: $this->faker->boolean,
            cantUsePhoneNumberUrl: $this->faker->url,
            recoveryUrl: $this->faker->url,
            recoveryWebUrl: $this->faker->url,
            repairPhoneNumberUrl: $this->faker->url,
            repairPhoneNumberWebUrl: $this->faker->url,
            securityCode: $this->fakeSecurityCode(),
            trustedPhoneNumber: $this->fakePhoneNumber(),
            trustedPhoneNumbers: new DataCollection(
                PhoneNumber::class,
                collect(range(1, 5))->map(fn() => $this->fakePhoneNumber())
            ),
            showAutoVerificationUI: $this->faker->boolean,
            supportsCustodianRecovery: $this->faker->boolean,
            supervisedChangePasswordFlow: $this->faker->boolean,
            supportsRecovery: $this->faker->boolean
        );
    }

    protected function fakePhoneNumber(): PhoneNumber
    {
        return new PhoneNumber(
            numberWithDialCode: $this->faker->phoneNumber,
            pushMode: 'sms',
            obfuscatedNumber: '•••• ••'.$this->faker->numberBetween(10, 99),
            lastTwoDigits: $this->faker->numberBetween(10, 99),
            id: $this->faker->unique()->numberBetween(1, 100)
        );
    }

    protected function fakeSecurityCode(): SecurityCode
    {
        return new SecurityCode(
            tooManyCodesSent: $this->faker->boolean,
            tooManyCodesValidated: $this->faker->boolean,
            securityCodeLocked: $this->faker->boolean,
            securityCodeCooldown: $this->faker->boolean,
            length: 6
        );
    }

    public function makeOne($attributes = []): SendDeviceSecurityCode
    {
        return SendDeviceSecurityCode::from($this->definition());
    }

    public function makes(int $count = 1): DataCollection
    {
        return new DataCollection(
            SendDeviceSecurityCode::class,
            collect(range(1, $count))->map(fn() => $this->makeOne())
        );
    }
}

