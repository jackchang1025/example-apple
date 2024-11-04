<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendDeviceSecurityCode;

uses(TestCase::class);

it('can create from json', function () {

    $json = file_get_contents(__DIR__.'/SendDeviceSecurityCode.json');

    $paymentConfig = SendDeviceSecurityCode::from($json);

    expect($paymentConfig)->toBeInstanceOf(SendDeviceSecurityCode::class)
        ->and($paymentConfig->otherTrustedDeviceClass)->toBeString()
        ->and($paymentConfig->aboutTwoFactorAuthenticationUrl)->toBeString()
        ->and($paymentConfig->trustedDeviceCount)->toBeInt()
        ->and($paymentConfig->securityCode)->toBeInstanceOf(SecurityCode::class);
});
