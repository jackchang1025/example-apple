<?php

namespace Modules\AppleClient\Tests\Unit\Service;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\Cookies\Cookies;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Header\HeaderSynchronize;
use Modules\AppleClient\Service\Store\CacheStore;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Http\Faking\MockClient;
use Spatie\LaravelData\Exceptions\CannotCreateData;
use Mockery;
use RuntimeException;
use Mockery\MockInterface;

uses(TestCase::class);

beforeEach(function () {
    // 设置基础测试数据
    $this->account     = 'test@example.com';
    $this->password    = 'test-password-123';
    $this->accountData = [
        'account'  => $this->account,
        'password' => $this->password,
    ];

    $this->cache      = app(CacheInterface::class);
    $this->dispatcher = app(Dispatcher::class);

    // Mock ProxyService
    $proxyService = Mockery::mock(ProxyService::class);
    $proxyService->shouldReceive('isProxyEnabled')->andReturn(false);
    $proxyService->shouldReceive('refreshProxy')->andReturn(null);
    app()->instance(ProxyService::class, $proxyService);

    // 创建 AppleBuilder 实例
    $this->builder = new AppleBuilder(
        cache: $this->cache,
        dispatcher: $this->dispatcher
    );
});

it('可以使用数组构建 Apple 实例', function () {


    $apple = $this->builder->build($this->accountData);

    expect($apple->getAccount()->account)->toBe($this->account)
        ->and($apple->getAccount()->password)->toBe($this->password);
});

it('可以使用 Account 实例构建 Apple 实例', function () {
    $account = new Account($this->account, $this->password);
    $apple   = $this->builder->build($account);

    expect($apple->getAccount())->toBe($account);
});

it('可以使用会话 ID 构建 Apple 实例', function () {

    $account = new Account($this->account, $this->password);

    $this->cache->set($account->getSessionId(), $account);

    $apple = $this->builder->build($account->getSessionId());

    expect($apple->getAccount())->toBeInstanceOf(Account::class);
});

it('正确加载 Cookie 配置', function () {


    $apple = $this->builder->build($this->accountData);

    expect($apple->getCookieJar())->toBeInstanceOf(Cookies::class);
});

it('正确加载 Header 配置', function () {
    $apple = $this->builder->build($this->accountData);

    expect($apple->getHeaderRepositories())->toBeInstanceOf(HeaderSynchronize::class);
});

it('正确加载代理配置', function () {
    $apple = $this->builder->build($this->accountData);

    expect($apple->getProxy())->toBeInstanceOf(ProxyService::class);
});

it('正确加载日志配置', function () {
    /** @var Mockery\MockInterface&LoggerInterface $logger */
    $logger = Mockery::mock(LoggerInterface::class);
    app()->instance(LoggerInterface::class, $logger);

    $apple = $this->builder->build($this->accountData);

    expect($apple->getLogger())->toBeInstanceOf(LoggerInterface::class);
});

it('正确配置重试策略', function () {


    $apple = $this->builder->build($this->accountData);

    expect($apple->getTries())->toBe(config('apple.retry.tries'))
        ->and($apple->getRetryInterval())->toBe(config('apple.retry.retryInterval'))
        ->and($apple->getUseExponentialBackoff())->toBe(config('apple.retry.useExponentialBackoff'))
        ->and($apple->getHandleRetry())->toBeInstanceOf(\Closure::class);
});

it('正确加载中间件', function () {
    $apple = $this->builder->build($this->accountData);

    $middleware = $apple->middleware()->getRequestPipeline()->getPipes();
    expect($middleware)->toHaveCount(2); // global 和 debug 中间件
});

it('处理无效账号时抛出异常', function () {
    $invalidAccount = ['invalid' => 'data'];

    $this->builder->build($invalidAccount);
})->throws(CannotCreateData::class, 'Could not create `Modules\AppleClient\Service\DataConstruct\Account');

it('处理会话过期时抛出异常', function () {
    $sessionId = 'expired-session';

    $this->builder->build($sessionId);
})->throws(RuntimeException::class, 'Session expired or invalid');
