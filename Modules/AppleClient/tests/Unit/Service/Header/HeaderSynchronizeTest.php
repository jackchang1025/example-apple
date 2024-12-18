<?php

namespace Modules\AppleClient\Tests\Unit\Service\Header;

use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Modules\AppleClient\Service\Header\HeaderSynchronize;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Repositories\ArrayStore;
use Psr\Http\Message\UriInterface;
use Mockery\MockInterface;
use Saloon\Enums\Method;

uses(TestCase::class);

beforeEach(function () {
    $this->store      = new ArrayStore();
    $this->headerSync = new HeaderSynchronize($this->store);
});

// 测试提取响应头部
it('测试提取响应头部', function () {
    /** @var MockInterface&Response */
    $response = Mockery::mock(Response::class);
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);
    /** @var MockInterface&UriInterface */
    $uri = Mockery::mock(UriInterface::class);

    // 设置模拟行为
    $uri->shouldReceive('getHost')->andReturn('example.com');
    $request->shouldReceive('getUri')->andReturn($uri);
    $response->shouldReceive('getPendingRequest')->andReturn($request);
    $response->shouldReceive('headers->all')->andReturn(['X-Test' => 'test-value']);

    $this->headerSync->extractHeader($response);

    expect($this->store->get('example.com'))
        ->toBe(['X-Test' => 'test-value']);
});

// 测试添加持久化头部
it('测试添加持久化头部', function () {
    $pendingRequest = Mockery::mock(PendingRequest::class);
    $headers        = Mockery::mock(\Saloon\Contracts\ArrayStore::class);


    $testConnector = new class extends \Saloon\Http\Connector {
        public function resolveBaseUrl(): string
        {
            return 'https://example.com';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore(['X-Connector']);
        }
    };
    $connector     = Mockery::mock($testConnector)->makePartial();

    $testRequest = new class extends \Saloon\Http\Request {

        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore(['X-Request']);
        }
    };
    $request     = Mockery::mock($testRequest)->makePartial();

    $pendingRequest->shouldReceive('getConnector')->andReturn($connector);
    $pendingRequest->shouldReceive('getRequest')->andReturn($request);

    $pendingRequest->shouldReceive('headers')->andReturn($headers);

    // 存储头部值到仓库
    $this->store->add('X-Connector', 'stored-value');
    $this->store->add('X-Request', 'request-value');

    // 验证头部添加
    $headers->shouldReceive('add')
        ->once()
        ->with('X-Connector', 'stored-value')
        ->andReturnSelf();
    $headers->shouldReceive('add')
        ->once()
        ->with('X-Request', 'request-value')
        ->andReturnSelf();

    $this->headerSync->withHeader($pendingRequest);
});

// 测试默认头部值
it('测试默认头部值', function () {
    $pendingRequest = Mockery::mock(PendingRequest::class);
    $headers        = Mockery::mock(\Saloon\Contracts\ArrayStore::class);

    // 创建带有默认值的测试连接器
    $testConnector = new class extends \Saloon\Http\Connector {
        public function resolveBaseUrl(): string
        {
            return 'https://example.com';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore(['X-Default' => fn() => 'default-value']);
        }
    };
    $connector     = Mockery::mock($testConnector)->makePartial();

    // 创建测试请求
    $testRequest = new class extends \Saloon\Http\Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore([]);
        }
    };
    $request     = Mockery::mock($testRequest)->makePartial();

    // 设置请求的依赖关系
    $pendingRequest->shouldReceive('getConnector')->andReturn($connector);
    $pendingRequest->shouldReceive('getRequest')->andReturn($request);
    $pendingRequest->shouldReceive('headers')->andReturn($headers);

    // 验证头部添加
    $headers->shouldReceive('add')
        ->once()
        ->with('X-Default', 'default-value')
        ->andReturnSelf();

    $this->headerSync->withHeader($pendingRequest);
});

// 测试空持久化头部
it('测试空持久化头部场景', function () {
    /** @var MockInterface&PendingRequest */
    $request = Mockery::mock(PendingRequest::class);

    $request->shouldReceive('getConnector->getPersistentHeaders->all')
        ->andReturn([]);
    $request->shouldReceive('getRequest->getPersistentHeaders->all')
        ->andReturn([]);

    $result = $this->headerSync->withHeader($request);

    expect($result)->toBe($request);
});

// 测试合并多个头部源
it('测试合并多个头部源', function () {
    $pendingRequest = Mockery::mock(PendingRequest::class);
    $headers        = Mockery::mock(\Saloon\Contracts\ArrayStore::class);

    // 创建测试连接器
    $testConnector = new class extends \Saloon\Http\Connector {
        public function resolveBaseUrl(): string
        {
            return 'https://example.com';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore(['X-Connector' => 'connector-value']);
        }
    };
    $connector     = Mockery::mock($testConnector)->makePartial();

    // 创建测试请求
    $testRequest = new class extends \Saloon\Http\Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore(['X-Request' => 'request-value']);
        }
    };
    $request     = Mockery::mock($testRequest)->makePartial();

    // 设置请求的依赖关系
    $pendingRequest->shouldReceive('getConnector')->andReturn($connector);
    $pendingRequest->shouldReceive('getRequest')->andReturn($request);
    $pendingRequest->shouldReceive('headers')->andReturn($headers);

    // 存储头部值到仓库
    $this->store->add('X-Connector', 'connector-value');
    $this->store->add('X-Request', 'request-value');

    // 验证头部添加
    $headers->shouldReceive('add')
        ->once()
        ->with('X-Connector', 'connector-value')
        ->andReturnSelf();
    $headers->shouldReceive('add')
        ->once()
        ->with('X-Request', 'request-value')
        ->andReturnSelf();

    $this->headerSync->withHeader($pendingRequest);
});

// 测试头部值回调函数
it('测试头部值回调函数', function () {
    $pendingRequest = Mockery::mock(PendingRequest::class);
    $headers        = Mockery::mock(\Saloon\Contracts\ArrayStore::class);

    // 创建测试连接器
    $testConnector = new class extends \Saloon\Http\Connector {
        public function resolveBaseUrl(): string
        {
            return 'https://example.com';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore(['X-Dynamic' => fn() => 'dynamic-value']);
        }
    };
    $connector     = Mockery::mock($testConnector)->makePartial();

    // 创建测试请求
    $testRequest = new class extends \Saloon\Http\Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }

        public function getPersistentHeaders(): ArrayStore
        {
            return new ArrayStore([]);
        }
    };
    $request     = Mockery::mock($testRequest)->makePartial();

    // 设置请求的依赖关系
    $pendingRequest->shouldReceive('getConnector')->andReturn($connector);
    $pendingRequest->shouldReceive('getRequest')->andReturn($request);
    $pendingRequest->shouldReceive('headers')->andReturn($headers);

    // 验证头部添加
    $headers->shouldReceive('add')
        ->once()
        ->with('X-Dynamic', 'dynamic-value')
        ->andReturnSelf();

    $this->headerSync->withHeader($pendingRequest);
});
