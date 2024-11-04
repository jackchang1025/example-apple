<?php

namespace Modules\AppleClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendPhoneVerificationCode;
use Spatie\LaravelData\DataCollection;

class SendPhoneVerificationCodeFactory extends Factory
{


    public function definition(): array
    {
        $phoneNumber = $this->fakePhoneNumber();

        return [
            'trustedPhoneNumbers'             => new DataCollection(
                PhoneNumber::class,
                collect(range(1, 3))->map(fn() => $this->fakePhoneNumber())
            ),
            'phoneNumber'                     => $phoneNumber,
            'trustedPhoneNumber'              => $phoneNumber, // 使用相同的电话号码作为信任号码
            'securityCode'                    => $this->fakeSecurityCode(),
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
            'hsa2Account'                     => $this->faker->boolean,
            'restrictedAccount'               => $this->faker->boolean,
            'managedAccount'                  => $this->faker->boolean,
        ];
    }

    protected function fakePhoneNumber(): PhoneNumber
    {
        $lastTwoDigits    = $this->faker->numberBetween(10, 99);
        $dialCode         = $this->faker->randomElement(['+86', '+852', '+1']);
        $obfuscatedNumber = str_repeat('•', 8).$lastTwoDigits;

        return new PhoneNumber(
            numberWithDialCode: $dialCode.' '.$obfuscatedNumber,
            pushMode: 'sms',
            obfuscatedNumber: $obfuscatedNumber,
            lastTwoDigits: $lastTwoDigits,
            id: $this->faker->unique()->numberBetween(1, 100)
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

    public function makeOne($attributes = []): SendPhoneVerificationCode
    {
        return SendPhoneVerificationCode::from($this->definition());
    }

    public function makes(int $count = 1): DataCollection
    {
        return new DataCollection(
            SendPhoneVerificationCode::class,
            collect(range(1, $count))->map(fn() => $this->makeOne())
        );
    }
}

