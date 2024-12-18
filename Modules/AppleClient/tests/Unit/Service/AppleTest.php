<?php

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Event;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\Cookies\Cookies;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Header\HeaderSynchronize;
use Modules\AppleClient\Service\Resources\Api\ApiResource;
use Modules\AppleClient\Service\Resources\Web\WebResource;
use Psr\Log\LoggerInterface;
use Saloon\Http\PendingRequest;

uses(TestCase::class);

beforeEach(function () {
    // 设置基础测试数据
    $this->account = new Account('test@example.com', 'test-password-123');
    $this->config  = new Config();
    $this->apple   = new Apple($this->account, $this->config);
});

// 测试基本属性获取
it('测试基本属性获取', function () {
    expect($this->apple->getAccount())->toBe($this->account)
        ->and($this->apple->getConfig())->toBe($this->config);
});

// 测试配置更新
it('测试配置更新', function () {
    $newConfig = ['debug' => true];

    $this->apple->withConfig($newConfig);

    expect($this->apple->config()->get('debug'))->toBeTrue();
});

// 测试事件分发器
it('测试事件分发器设置', function () {
    $dispatcher = Event::getFacadeRoot();

    $this->apple->withDispatcher($dispatcher);

    expect($this->apple->getDispatcher())->toBe($dispatcher);
});

// 测试资源获取
it('测试资源获取', function () {
    expect($this->apple->getWebResource())->toBeInstanceOf(WebResource::class)
        ->and($this->apple->getApiResources())->toBeInstanceOf(ApiResource::class);
});

// 测试中间件
it('测试中间件添加', function () {
    $this->apple->middleware()->onRequest(function (PendingRequest $request) {
        return $request;
    });

    expect($this->apple->middleware()->getRequestPipeline()->getPipes())->toHaveCount(1);
});

// 测试 Cookie 设置
it('测试 Cookie 设置', function () {
    $cookies = mock(Cookies::class);

    $this->apple->withCookies($cookies);

    expect($this->apple->getCookieJar())->toBe($cookies);
});

// 测试 Header 同步设置
it('测试 Header 同步设置', function () {
    $headerSync = mock(HeaderSynchronize::class);

    $this->apple->withHeaderRepositories($headerSync);

    expect($this->apple->getHeaderRepositories())->toBe($headerSync);
});

// 测试日志设置
it('测试日志设置', function () {
    $logger = mock(LoggerInterface::class);

    $this->apple->withLogger($logger);

    expect($this->apple->getLogger())->toBe($logger);
});

// 测试重试设置
it('测试重试设置', function () {
    $this->apple->withTries(5)
        ->withRetryInterval(1000)
        ->withUseExponentialBackoff();

    expect($this->apple->getTries())->toBe(5)
        ->and($this->apple->getRetryInterval())->toBe(1000)
        ->and($this->apple->getUseExponentialBackoff())->toBeTrue();
});

// 测试条件判断
it('测试条件判断功能', function () {
    $result = $this->apple
        ->when(true, function ($apple) {
            return $apple->withTries(5);
        })
        ->unless(false, function ($apple) {
            return $apple->withRetryInterval(1000);
        });

    expect($result)->toBe($this->apple)
        ->and($result->getTries())->toBe(5)
        ->and($result->getRetryInterval())->toBe(1000);
});
