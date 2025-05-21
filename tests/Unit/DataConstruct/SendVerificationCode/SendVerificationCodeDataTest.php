<?php

use Illuminate\Foundation\Testing\TestCase;
use Spatie\LaravelData\DataCollection;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendPhoneVerificationCode;
use Weijiajia\SaloonphpAppleClient\DataConstruct\PhoneNumber;
use Weijiajia\SaloonphpAppleClient\DataConstruct\SecurityCode;

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
