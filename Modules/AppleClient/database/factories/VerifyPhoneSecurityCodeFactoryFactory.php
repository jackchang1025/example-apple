<?php

namespace Modules\AppleClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;
use Modules\AppleClient\Service\DataConstruct\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Spatie\LaravelData\DataCollection;

class VerifyPhoneSecurityCodeFactoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trustedPhoneNumbers'             => new DataCollection(
                PhoneNumber::class,
                collect(range(1, 5))->map(fn() => $this->fakePhoneNumber())
            ),
            'phoneNumber'                     => $this->fakePhoneNumber(),
            'securityCode'                    => $this->fakeSecurityCode(),
            'trustedPhoneNumber'              => $this->fakePhoneNumber(),
            'mode'                            => $this->faker->randomElement(['sms', 'voice']),
            'type'                            => 'verification',
            'authenticationType'              => 'hsa2',
            'recoveryUrl'                     => $this->faker->url,
            'cantUsePhoneNumberUrl'           => $this->faker->url,
            'recoveryWebUrl'                  => $this->faker->url,
            'repairPhoneNumberUrl'            => $this->faker->url,
            'repairPhoneNumberWebUrl'         => $this->faker->url,
            'aboutTwoFactorAuthenticationUrl' => $this->faker->url,
            'autoVerified'                    => $this->faker->boolean,
        ];
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
            valid: $this->faker->boolean,
            code: $this->faker->numerify('######')
        );
    }

    public function makeOne($attributes = [])
    {
        return VerifyPhoneSecurityCode::from($this->definition());
    }
}

