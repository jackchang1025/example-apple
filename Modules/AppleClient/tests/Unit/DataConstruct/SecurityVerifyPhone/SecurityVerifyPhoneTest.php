<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\PhoneNumberVerification;
use Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone;

uses(TestCase::class);

it('can create from json', function () {

    $json = file_get_contents(__DIR__.'/SecurityVerifyPhone.json');

    $SendDeviceSecurityCode = SecurityVerifyPhone::from($json);

    expect($SendDeviceSecurityCode)->toBeInstanceOf(SecurityVerifyPhone::class)
        ->and($SendDeviceSecurityCode->phoneNumberVerification)->toBeNull()
        ->and($SendDeviceSecurityCode->phoneNumber)->toBeInstanceOf(PhoneNumber::class);
});
it('can create from json2', function () {

    $json = file_get_contents(__DIR__.'/SecurityVerifyPhone2.json');

    $SendDeviceSecurityCode = SecurityVerifyPhone::from($json);

    expect($SendDeviceSecurityCode)->toBeInstanceOf(SecurityVerifyPhone::class)
        ->and($SendDeviceSecurityCode->phoneNumberVerification)->toBeInstanceOf(PhoneNumberVerification::class)
        ->and($SendDeviceSecurityCode->phoneNumber)->toBeNull();
});


