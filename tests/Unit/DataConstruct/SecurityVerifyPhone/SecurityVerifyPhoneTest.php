<?php

use Illuminate\Foundation\Testing\TestCase;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumber;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumberVerification;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;


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


