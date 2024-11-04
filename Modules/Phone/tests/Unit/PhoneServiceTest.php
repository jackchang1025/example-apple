<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\Phone\Services\PhoneService;

uses(TestCase::class);


test('it can be tested', function () {

    $phoneService = new PhoneService('12345678901');

    expect($phoneService->isValid())->toBeFalse();
});

test('it can be tested1', function () {

    $phoneService = new PhoneService('13067772321');

    expect($phoneService->isValid())->toBeFalse();
});

test('it can be tested2', function () {

    $phoneService = new PhoneService('13067772321', ['CN', 'HK']);

    expect($phoneService->isValid())->toBeTrue()
        ->and($phoneService->getCountry())->toBe('CN');
});

test('it can be tested3', function () {

    $phoneService = new PhoneService('+8613067772321');

    expect($phoneService->isValid())->toBeTrue();
});

test('it can be tested4', function () {

    $phoneService = new PhoneService('+85297403063');

    expect($phoneService->isValid())->toBeTrue();
});

test('it can be tested5', function () {

    $phoneService = new PhoneService('+85297403063');

    expect($phoneService->getCountry())->toBe('HK');
});

test('it can be tested6', function () {

    $phoneService = new PhoneService('+8613067772321');

    expect($phoneService->getCountry())->toBe('CN');
});

test('it can be tested7', function () {

    $phoneService = new PhoneService('13067772321', ['CN']);

    expect($phoneService->isValid())->toBeTrue()
        ->and($phoneService->getCountry())->toBe('CN')
        ->and($phoneService->getRawNumber())->toBe('13067772321')
        ->and($phoneService->format())->toBe('+86 130 6777 2321');
});

test('it can be tested8', function () {

    $phoneService = new PhoneService('+8613067772321', ['CN']);

    expect($phoneService->isValid())->toBeTrue()
        ->and($phoneService->getCountryCode())->toBe(86)->dump();
});




