<?php

namespace Modules\AppleClient\Tests\Unit\Service\Cookies;

use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Modules\AppleClient\Service\Cookies\CookieJar;
use Modules\AppleClient\Service\Cookies\Cookies;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Modules\AppleClient\Service\Cookies\SetCookie;
use Psr\SimpleCache\CacheInterface;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Mockery\MockInterface;
use stdClass;
use TypeError;

uses(TestCase::class);

// 创建一个测试类来使用 HasCookie trait
class TestClass
{
    use HasCookie;
}

beforeEach(function () {
    // 设置基础测试数据
    /** @var MockInterface&TestClass */
    $this->testClass = Mockery::mock(TestClass::class)->makePartial();

    // 创建一个符合 CacheInterface 的 mock
    /** @var MockInterface&CacheInterface */
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->cache->shouldReceive('get')->andReturn([]);
    $this->cache->shouldReceive('set')->andReturn(true);

    // 创建基础的 Cookie 对象
    $this->cookies = new Cookies(
        cache: $this->cache,
        key: 'test-key',
        ttl: 3600
    );
});
// 测试设置 null
test('withCookies sets cookieJar to null when given null', function () {
    $dummy = new TestClass();
    $dummy->withCookies(null);
    expect($dummy->getCookieJar())->toBeNull();
});

// 测试设置 Cookie
it('测试设置 Cookie 对象', function () {
    $this->testClass->withCookies($this->cookies);

    expect($this->testClass->getCookieJar())->toBe($this->cookies);
});

// 测试设置数组形式的 Cookie
it('测试设置数组形式的 Cookie', function () {
    $cookieArray = [
        new SetCookie([
            'Name'   => 'test-cookie',
            'Value'  => 'test-value',
            'Domain' => 'example.com',
        ]),
    ];

    $this->testClass->withCookies($cookieArray);

    expect($this->testClass->getCookieJar())
        ->toBeInstanceOf(CookieJar::class)
        ->and($this->testClass->getCookieJar()?->count())->toBe(1);
});

// 测试清除 Cookie
it('测试清除 Cookie', function () {
    $this->testClass->withCookies(null);

    expect($this->testClass->getCookieJar())->toBeNull();
});

// 测试中间件注册
it('测试 Cookie 中间件注册', function () {
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);
    $request->shouldReceive('getConnector->middleware->onRequest')->once();
    $request->shouldReceive('getConnector->middleware->onResponse')->once();

    $this->testClass->shouldReceive('requestPipelineExists')
        ->once()
        ->with($request, 'withCookieHeader')
        ->andReturnFalse();

    $this->testClass->shouldReceive('responsePipelineExists')
        ->once()
        ->with($request, 'extractCookies')
        ->andReturnFalse();

    // 设置 Cookie 并启动
    $this->testClass->withCookies($this->cookies);
    $this->testClass->bootHasCookie($request);
});

// 测试请求中间件处理
it('测试请求中间件处理 Cookie', function () {
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);

    // 创建中间件 mock
    /** @var MockInterface&\Saloon\Helpers\MiddlewarePipeline */
    $middleware = Mockery::mock(\Saloon\Helpers\MiddlewarePipeline::class);
    $middleware->shouldReceive('onRequest')
        ->once()
        ->andReturnUsing(function ($callback) use ($request, $middleware) {
            $callback($request);

            return $middleware;
        });

    $request->shouldReceive('getConnector->middleware')
        ->andReturn($middleware);

    // 创建 Cookie mock
    /** @var MockInterface&CookieJar */
    $cookieJar = Mockery::mock(CookieJar::class);
    $cookieJar->shouldReceive('withCookieHeader')
        ->once()
        ->with($request)
        ->andReturn($request);

    $this->testClass->shouldReceive('requestPipelineExists')
        ->once()
        ->with($request, 'withCookieHeader')
        ->andReturnFalse();

    $this->testClass->shouldReceive('responsePipelineExists')
        ->once()
        ->with($request, 'extractCookies')
        ->andReturnTrue();

    // 设置 Cookie 并验证
    $this->testClass->withCookies($cookieJar);
    $this->testClass->bootHasCookie($request);
});

// 测试响应中间件处理
it('测试响应中间件处理 Cookie', function () {
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);
    /** @var MockInterface&Response */
    $response = Mockery::mock(Response::class);

    // 创建中间件 mock
    /** @var MockInterface&\Saloon\Helpers\MiddlewarePipeline */
    $middleware = Mockery::mock(\Saloon\Helpers\MiddlewarePipeline::class);
    $middleware->shouldReceive('onRequest');
    $middleware->shouldReceive('onResponse')
        ->once()
        ->andReturnUsing(function ($callback) use ($response, $middleware) {
            $callback($response);

            return $middleware;
        });

    $request->shouldReceive('getConnector->middleware')
        ->andReturn($middleware);

    // 创建 Cookie mock
    /** @var MockInterface&CookieJar */
    $cookieJar = Mockery::mock(CookieJar::class);
    $cookieJar->shouldReceive('extractCookies')
        ->once()
        ->with($request, $response)
        ->andReturnNull();

    $this->testClass->shouldReceive('requestPipelineExists')
        ->once()
        ->with($request, 'withCookieHeader')
        ->andReturnTrue();

    $this->testClass->shouldReceive('responsePipelineExists')
        ->once()
        ->with($request, 'extractCookies')
        ->andReturnFalse();

    // 设置 Cookie 并验证
    $this->testClass->withCookies($cookieJar);
    $this->testClass->bootHasCookie($request);
});

// 测试中间件不重复添加
it('测试 Cookie 中间件不重复添加', function () {
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);
    $request->shouldReceive('getConnector->middleware->onRequest')->never();
    $request->shouldReceive('getConnector->middleware->onResponse')->never();

    $this->testClass->shouldReceive('requestPipelineExists')
        ->times(2)
        ->with($request, 'withCookieHeader')
        ->andReturnTrue();

    $this->testClass->shouldReceive('responsePipelineExists')
        ->times(2)
        ->with($request, 'extractCookies')
        ->andReturnTrue();

    // 多次调用 bootHasCookie，确保中间件不重复添加
    $this->testClass->withCookies($this->cookies);
    $this->testClass->bootHasCookie($request);
    $this->testClass->bootHasCookie($request);
});

// 测试 Cookie 持久化
it('测试 Cookie 持久化功能', function () {
    /** @var MockInterface&CacheInterface */
    $cache = Mockery::mock(CacheInterface::class);
    $cache->shouldReceive('get')->andReturn([]);
    $cache->shouldReceive('set')
        ->with(
            'cookie:test-key',
            Mockery::type('array'),
            3600
        )
        ->andReturn(true);

    // 创建新的 Cookies 实例
    $cookies = new Cookies(
        cache: $cache,
        key: 'test-key',
        ttl: 3600
    );

    // 创建一个新的 Cookie
    $cookie = new SetCookie([
        'Name'     => 'session',
        'Value'    => 'abc123',
        'Domain'   => 'example.com',
        'Path'     => '/',
        'Expires'  => time() + 3600,
        'Secure'   => true,
        'HttpOnly' => true,
    ]);

    // 验证 Cookie 被正确保存
    $cookies->setCookie($cookie);
    expect($cookies->count())->toBe(1);

    // 手动触发持久化
    $cookies->save();

    // 验证 Cookie 持久化到缓存
    $cache->shouldHaveReceived('set', [
        'cookie:test-key',
        Mockery::type('array'),
        3600,
    ]);
});

// 测试无效的 Cookie 设置
it('测试设置无效的 Cookie 类型', function () {
    $this->testClass->withCookies(new stdClass());
})->throws(TypeError::class);
