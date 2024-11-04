<?php


use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleAccountManager;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\IpProxyManager\Service\ProxyService;
use Modules\PhoneCode\Service\PhoneConnector;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cache          = Mockery::mock(CacheInterface::class);
    $this->logger         = Mockery::mock(LoggerInterface::class);
    $this->proxyService   = Mockery::mock(ProxyService::class);
    $this->phoneConnector = Mockery::mock(PhoneConnector::class);

    $this->factory = new AppleAccountManagerFactory(
        $this->cache,
        $this->logger,
        $this->proxyService,
        $this->phoneConnector
    );
});

test('create method returns AppleAccountManager instance', function () {
    $account = Account::factory()->create();

    $this->cache->shouldReceive('get')->times(2)->andReturn([]);
    $this->proxyService->shouldReceive('isProxyEnabled')->once()->andReturnFalse();


    $manager = $this->factory->create($account);

    expect($manager)->toBeInstanceOf(AppleAccountManager::class)
        ->and($manager->getAccount()->id)->toBe($account->id);
});

test('create method accepts array instead of Account object', function () {
    $accountArray = ['account' => 'test@example.com', 'password' => 'password123'];

    $this->cache->shouldReceive('get')->times(2)->andReturn([]);
    $this->proxyService->shouldReceive('isProxyEnabled')->once()->andReturnFalse();

    $manager = $this->factory->create($accountArray);

    expect($manager)->toBeInstanceOf(AppleAccountManager::class)
        ->and($manager->getAccount()->account)->toBe('test@example.com');
});

test('create method uses provided config', function () {
    $account = Account::factory()->create();
    $config  = ['some_config' => 'value'];

    $this->cache->shouldReceive('get')->times(2)->andReturn([]);
    $this->proxyService->shouldReceive('isProxyEnabled')->once()->andReturnFalse();

    $manager = $this->factory->create($account, $config);

    expect($manager)->toBeInstanceOf(AppleAccountManager::class)
        ->and($manager->config())->toHaveKey('some_config', 'value');
});


test('create method uses proxy if enabled', function () {
    $account = Account::factory()->create();

    $this->cache->shouldReceive('get')->times(2)->andReturn([]);
    $this->proxyService->shouldReceive('isProxyEnabled')->once()->andReturn(true);
    $this->proxyService->shouldReceive('getProxy')->once()->andReturn(['host' => 'proxy.example.com', 'port' => 8080]);

    $manager = $this->factory->create($account);

    expect($manager)->toBeInstanceOf(AppleAccountManager::class)
        ->and($manager->config())->toHaveKey('proxy');
});

test('create method does not use proxy if disabled', function () {
    $account = Account::factory()->create();

    $this->proxyService->shouldReceive('isProxyEnabled')->once()->andReturn(false);

    $manager = $this->factory->create($account);

    expect($manager)->toBeInstanceOf(AppleAccountManager::class)
        ->and($manager->config())->not->toHaveKey('proxy');
});

test('create method throws exception for invalid account data', function () {
    $invalidAccount = ['invalid' => 'data'];

    expect(fn() => $this->factory->create($invalidAccount))
        ->toThrow(\InvalidArgumentException::class);
});
