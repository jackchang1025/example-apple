<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Delegate;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\GameCenter\GameCenter;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Ids\Ids;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\LoginDelegates;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\MobileMe\MobileMe;
use Modules\AppleClient\Service\Helpers\PlistXmlParser;

uses(TestCase::class);

it('can parse plist xml', function () {

    $xmlContent = (file_get_contents(base_path('/Modules/AppleClient/tests/Unit/Files/login.xml')));
    $parser     = new PlistXmlParser();
    $result     = $parser->xmlParse(simplexml_load_string($xmlContent));

    $loginDelegates = LoginDelegates::from($result);

    expect($loginDelegates)
        ->toBeInstanceOf(LoginDelegates::class)
        ->and($loginDelegates->status)
        ->toBe(0)
        ->and($loginDelegates->dsid)
        ->not
        ->toBeNull()
        ->and($loginDelegates->delegates)
        ->toBeInstanceOf(Delegate::class)
        ->and($loginDelegates->delegates->mobileMeService)
        ->toBeInstanceOf(MobileMe::class)
        ->and($loginDelegates->delegates->gameCenterService)
        ->toBeInstanceOf(GameCenter::class)
        ->and($loginDelegates->delegates->idsService)
        ->toBeInstanceOf(Ids::class);
});

it('can parse plist login fail xml', function () {

    $xmlContent = (file_get_contents(base_path('/Modules/AppleClient/tests/Unit/Files/loginfail.xml')));
    $parser     = new PlistXmlParser();
    $result     = $parser->xmlParse(simplexml_load_string($xmlContent));

    $loginDelegates = LoginDelegates::from($result);

    expect($loginDelegates)
        ->toBeInstanceOf(LoginDelegates::class)
        ->and($loginDelegates->status)
        ->toBe(1)
        ->and($loginDelegates->statusMessage)
        ->toBe('We can not process your request, please try again later.')
        ->and($loginDelegates->dsid)
        ->toBeNull()
        ->and($loginDelegates->delegates)
        ->toBeNull();
});

it('can parse plist login auth xml', function () {

    $xmlContent = (file_get_contents(base_path('/Modules/AppleClient/tests/Unit/Files/auth.xml')));
    $parser     = new PlistXmlParser();
    $result     = $parser->xmlParse(simplexml_load_string($xmlContent));

    $loginDelegates = LoginDelegates::from($result);

    expect($loginDelegates)
        ->toBeInstanceOf(LoginDelegates::class)
        ->and($loginDelegates->status)
        ->toBe(0)
        ->and($loginDelegates->statusMessage)
        ->toBeNull()
        ->and($loginDelegates->dsid)
        ->not
        ->toBeNull()
        ->and($loginDelegates->delegates)
        ->toBeInstanceOf(Delegate::class)
        ->and($loginDelegates->delegates->idsService->status)
        ->toBe(5000);
});
