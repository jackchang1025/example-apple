<?php

namespace Modules\AppleClient\Tests\Unit\Service\Header;

use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Modules\AppleClient\Service\Header\HasHeaderSynchronize;
use Modules\AppleClient\Service\Header\HeaderSynchronize;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Repositories\ArrayStore;
use Mockery\MockInterface;

uses(TestCase::class);

// 修改测试类名
class HeaderSynchronizeTestClass
{
    use HasHeaderSynchronize;
}

beforeEach(function () {
    // 设置基础测试数据
    /** @var MockInterface&HeaderSynchronizeTestClass */
    $this->testClass = Mockery::mock(HeaderSynchronizeTestClass::class)->makePartial();
});

// 测试设置数组形式的 header 仓库
it('测试设置数组形式的 header 仓库', function () {
    $headers = ['X-Test' => 'test-value'];
    $this->testClass->withHeaderRepositories($headers);

    expect($this->testClass->getHeaderRepositories())
        ->toBeInstanceOf(HeaderSynchronize::class);
});

// 测试设置 HeaderSynchronize 实例
it('测试设置 HeaderSynchronize 实例', function () {
    $headerSync = new HeaderSynchronize(new ArrayStore());
    $this->testClass->withHeaderRepositories($headerSync);

    expect($this->testClass->getHeaderRepositories())->toBe($headerSync);
});

// 测试设置 null
it('测试设置 null 清除 header 仓库', function () {
    $this->testClass->withHeaderRepositories(null);
    expect($this->testClass->getHeaderRepositories())->toBeNull();
});

// 测试中间件注册
it('测试 header 中间件注册', function () {
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);
    /** @var MockInterface&HeaderSynchronizeInterface */
    $headerSync = Mockery::mock(HeaderSynchronizeInterface::class);

    // 设置中间件 mock
    $middleware = Mockery::mock(\Saloon\Helpers\MiddlewarePipeline::class);
    $middleware->shouldReceive('onRequest')->once()->andReturnSelf();
    $middleware->shouldReceive('onResponse')->once()->andReturnSelf();

    $request->shouldReceive('middleware')->andReturn($middleware);

    // 设置 header 同步并启动
    $this->testClass->withHeaderRepositories($headerSync);
    $this->testClass->bootHasHeaderSynchronize($request);
});

// 测试没有 header 仓库时不注册中间件
it('测试没有 header 仓库时不注册中间件', function () {
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);
    $request->shouldReceive('middleware')->never();

    $this->testClass->bootHasHeaderSynchronize($request);
});
