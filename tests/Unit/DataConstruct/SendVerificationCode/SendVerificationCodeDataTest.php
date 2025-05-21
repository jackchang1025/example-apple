<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendPhoneVerificationCode;
use Spatie\LaravelData\DataCollection;

uses(TestCase::class);

it('can create from json', function () {

    $json = file_get_contents(__DIR__.'/SendVerificationCode.json');

    $paymentConfig = SendPhoneVerificationCode::from($json);

    expect($paymentConfig)->toBeInstanceOf(SendPhoneVerificationCode::class)
        ->and($paymentConfig->trustedPhoneNumbers)->toHaveCount(2)
        ->and($paymentConfig->trustedPhoneNumbers[0])->toBeInstanceOf(PhoneNumber::class)
        ->and($paymentConfig->trustedPhoneNumbers[0]->id)->toBe(2)
        ->and($paymentConfig->trustedPhoneNumbers[0]->pushMode)->toBe('sms')
        ->and($paymentConfig->trustedPhoneNumbers)->toBeInstanceOf(DataCollection::class)
        ->and($paymentConfig->phoneNumber)->toBeInstanceOf(PhoneNumber::class)
        ->and($paymentConfig->trustedPhoneNumber)->toBeInstanceOf(PhoneNumber::class)
        ->and($paymentConfig->securityCode)->toBeInstanceOf(SecurityCode::class);
});
