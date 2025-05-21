<?php

use Illuminate\Foundation\Testing\TestCase;
use Weijiajia\SaloonphpAppleClient\Helpers\PlistXmlParser;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\LoginDelegates\Delegate;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\LoginDelegates\GameCenter\GameCenter;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\LoginDelegates\Ids\Ids;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\LoginDelegates\LoginDelegates;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\LoginDelegates\MobileMe\MobileMe;


it('can parse plist xml', function () {

    $xmlContent = getFixturesFile('login.xml');
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

    $xmlContent = getFixturesFile('loginfail.xml');
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

    $xmlContent = getFixturesFile('auth.xml');
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
