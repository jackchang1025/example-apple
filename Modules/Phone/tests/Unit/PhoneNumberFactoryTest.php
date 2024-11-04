<?php

namespace Modules\Phone\Tests;

use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\TestCase;
use Modules\Phone\Services\PhoneNumberFactory;
use Modules\Phone\Services\PhoneService;

uses(TestCase::class);


test('it can be tested', function () {

    $phoneNumberFactory = new PhoneNumberFactory();

    $phoneService = $phoneNumberFactory->create('12345678901');

    expect($phoneService)->toBeInstanceOf(PhoneService::class)
        ->and($phoneService->getDefaultCountry())->toBe([SecuritySetting::first()?->configuration['country_code']])
        ->and($phoneService->getDefaultFormat())->toBe(config('phone.format'));
});
