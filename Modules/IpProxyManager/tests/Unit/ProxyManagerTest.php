<?php

namespace Tests\Feature\Services;

use App\Models\ProxyConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Modules\IpProxyManager\Service\Exception\ProxyModelNotFoundException;
use Modules\IpProxyManager\Service\ProxyManager;
use Modules\IpProxyManager\Service\ProxyService;
use Modules\IpProxyManager\Service\Wandou\DTO\AccountPasswordDto;
use Modules\IpProxyManager\Service\Wandou\WandouConnector;
use Saloon\Http\Faking\{MockClient};

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

beforeEach(function () {
    MockClient::destroyGlobal();
});

function createProxyConfig(array $drivers = [], bool $isActive = true): ProxyConfiguration
{
    $defaultDriver = array_key_first($drivers);

    return ProxyConfiguration::factory()->create([
        'configuration'     => array_merge([
            'default_driver' => $defaultDriver,
        ],
            array_map(function ($config) {
                return $config;
            }, $drivers)),
        'is_active'         => $isActive,
        'proxy_enabled'     => false,
        'ipaddress_enabled' => false,
    ]);
}


test('manager default driver ', function () {
    createProxyConfig([
        'wandou' => [
            // mode 未设置
            'time'     => 10,
            'mode'     => 'flow',
            'session'  => 'test_session',
            'username' => 'test_username',
            'password' => 'test_password',
            'host'     => 'test_password',
            'port'     => 'test_password',
        ],
    ]);

    $manager = app(ProxyManager::class);

    $proxy = $manager->connector();

    expect($proxy)->toBeInstanceOf(ProxyService::class)
        ->and($proxy->getConnector())->toBeInstanceOf(WandouConnector::class)
        ->and($proxy->getDto())->toBeInstanceOf(AccountPasswordDto::class);
});

test('manager throws exception for missing mode', function () {
    createProxyConfig([
        'huashengdaili' => [
            // mode 未设置
            'time' => 10,
        ],
    ]);

    $manager = app(ProxyManager::class);

    expect(fn() => $manager->connector())
        ->toThrow(
            ProxyModelNotFoundException::class,
            'Mode not configured for driver huashengdaili'
        );
});

test('manager uses configured mode', function () {
    createProxyConfig([
        'huashengdaili' => [
            'mode'    => 'api',
            'time'    => 10,
            'session' => 'test_session',
        ],
        'wandou'        => [
            'mode'     => 'flow',
            'app_key'  => 'test_key',
            'session'  => 'test_session',
            'username' => 'test_username',
            'password' => 'test_password',
            'host'     => 'test_password',
            'port'     => 'test_password',
        ],
    ]);

    $manager = app(ProxyManager::class);

    // 测试 huashengdaili api 模式
    $service1 = $manager->connector();

    expect($service1->getDto()->all())
        ->toHaveKey('mode', 'api')
        ->toHaveKey('time', 10);

    // 测试 wandou flow 模式
    $service2 = $manager->connector('wandou');
    expect($service2->getDto()->all())
        ->toHaveKey('mode', 'flow')
        ->toHaveKey('app_key', 'test_key');
});

test('manager throws exception for invalid mode', function () {
    createProxyConfig([
        'huashengdaili' => [
            'mode' => 'invalid_mode',
            'time' => 10,
        ],
    ]);

    $manager = app(ProxyManager::class);

    expect(fn() => $manager->connector())
        ->toThrow(
            ProxyModelNotFoundException::class,
            'Mode invalid_mode not found for driver huashengdaili'
        );
});

test('manager maintains mode when switching drivers', function () {
    createProxyConfig([
        'huashengdaili' => [
            'mode'          => 'api',
            'shared_config' => 'value',
            'session'       => 'test_session',
        ],
        'wandou'        => [
            'mode'            => 'flow',
            'specific_config' => 'test',
            'app_key'         => 'test_key',
            'session'         => 'test_session',
            'username'        => 'test_username',
            'password'        => 'test_password',
            'host'            => 'test_password',
            'port'            => 'test_password',
        ],
    ]);

    $manager = app(ProxyManager::class);

    // 验证每个驱动使用自己的模式
    $service1 = $manager->connector();
    expect($service1->getDto()->all())
        ->toHaveKey('mode', 'api');

    $service2 = $manager->connector('wandou');
    expect($service2->getDto()->all())
        ->toHaveKey('mode', 'flow');
});

test('manager properly merges mode-specific configurations', function () {
    // 设置默认配置
    Config::set('ipproxymanager.providers.wandou.mode.dynamic.default_config', [
        'default_setting' => 'default',
        'overridable'     => 'original',
    ]);

    // 创建活动配置
    createProxyConfig([
        'wandou' => [
            'mode'        => 'dynamic',
            'overridable' => 'custom',
            'specific'    => 'value',
            'app_key'     => 'test_key',

        ],
    ]);

    $manager = app(ProxyManager::class);
    $service = $manager->connector('wandou');

    expect($service->getDto()->all())
        ->toHaveKey('mode', 'dynamic')
        ->toHaveKey('default_setting', 'default')    // 保留默认配置
        ->toHaveKey('overridable', 'custom')         // 覆盖默认配置
        ->toHaveKey('specific', 'value');            // 添加特定配置
});
