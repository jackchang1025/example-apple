<?php

use App\Models\Account;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleAccountManager;
use Modules\IpAddress\Service\IpService;
use Modules\IpProxyManager\Service\ProxyService;
use Modules\PhoneCode\Service\PhoneConnector;

uses(TestCase::class);

// 模拟依赖
beforeEach(function () {
    $this->account        = Mockery::mock(Account::class);
    $this->client         = Mockery::mock(\Modules\AppleClient\Service\AppleClient::class);
    $this->proxyService   = Mockery::mock(ProxyService::class);
    $this->ipService      = Mockery::mock(IpService::class);
    $this->phoneConnector = Mockery::mock(PhoneConnector::class);
});

test('AppleAccountManager can be instantiated', function () {
    $manager = new AppleAccountManager($this->account);
    expect($manager)->toBeInstanceOf(AppleAccountManager::class);
});

test('AppleAccountManager can set and get account', function () {
    $manager = new AppleAccountManager($this->account);
    expect($manager->getAccount())->toBe($this->account);

    $newAccount = Mockery::mock(Account::class);
    $manager->withAccount($newAccount);
    expect($manager->getAccount())->toBe($newAccount);
});

test('AppleAccountManager can set and get client', function () {
    $manager = new AppleAccountManager($this->account);
    $manager->withClient($this->client);
    expect($manager->getClient())->toBe($this->client);
});

test('AppleAccountManager can call methods on client', function () {
    $manager = new AppleAccountManager($this->account);
    $manager->withClient($this->client);

    $this->client->shouldReceive('someMethod')->once()->andReturn('result');

    expect($manager->someMethod())->toBe('result');
});

test('AppleAccountManager calls save on destruct', function () {

    $manager = new AppleAccountManager(Account::from(['account' => '1234567890', 'password' => '1234567890']));

    try {
        $serializer = $manager->serializer();
    } catch (Exception $e) {
        dd($e);
    }
    dd($serializer);//AppleAccountManager::unserializer($serializer)
});


