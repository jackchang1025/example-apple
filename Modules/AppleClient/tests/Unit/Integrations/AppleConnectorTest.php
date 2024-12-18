<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Cookies\CookieJarInterface;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Integrations\AppleConnector;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;
use Saloon\Helpers\MiddlewarePipeline;
use Saloon\Http\PendingRequest;

uses(TestCase::class);

/**
 * 用于测试的具体AppleConnector实现
 */
class TestAppleConnector extends AppleConnector
{
    public function resolveBaseUrl(): string
    {
        return 'https://test.example.com';
    }

    // 暴露protected方法用于测试
    public function exposeFormatRequestLog(PendingRequest $request): ?PendingRequest
    {
        return $this->formatRequestLog($request);
    }

    // 暴露defaultConfig方法用于测试
    public function exposeDefaultConfig(): array
    {
        return $this->defaultConfig();
    }
}

beforeEach(function () {
    /** @var Apple $apple */
    $this->apple = Mockery::mock(Apple::class);

    // 模拟构造函数中需要的方法
    $this->apple->shouldReceive('getTries')
        ->once()
        ->andReturn(3);

    $this->apple->shouldReceive('getRetryInterval')
        ->once()
        ->andReturn(1000);

    $this->apple->shouldReceive('getUseExponentialBackoff')
        ->once()
        ->andReturn(true);

    $this->appleConnector = new TestAppleConnector($this->apple);
});

/**
 * 测试默认配置设置
 */
test('test default config is set correctly', function () {
    // 模拟 Apple 类的 config 方法返回
    $config = ['timeout' => 60, 'retries' => 3];
    $this->apple->shouldReceive('config->all')
        ->once()
        ->andReturn($config);

    $result = $this->appleConnector->config();

    // 验证配置值是否正确
    expect($result->all())->toBe($config);
    // 验证返回类型
    expect($result)->toBeInstanceOf(\Saloon\Repositories\ArrayStore::class);
});

/**
 * 测试代理服务设置
 */
test('test proxy service configuration', function () {
    // 模拟代理服务
    $proxyService = Mockery::mock(ProxyService::class);

    // 设置 Apple 实例返回代理服务
    $this->apple->shouldReceive('getProxy')
        ->once()
        ->andReturn($proxyService);

    $result = $this->appleConnector->getProxy();
    expect($result)->toBe($proxyService);
});

/**
 * 测试连接超时设置
 */
test('test connect timeout configuration', function () {
    // 模拟配置返回
    $this->apple->shouldReceive('config->get')
        ->with('connectTimeout', 60)
        ->once()
        ->andReturn(30);

    $timeout = $this->appleConnector->connectTimeout();
    expect($timeout)->toBe(30);
});

/**
 * 测试重试机制
 */
test('test retry mechanism', function () {
    // 模拟异常和请求
    $exception = Mockery::mock(RequestException::class);
    $request   = Mockery::mock(Request::class);

    // 设置自定义重试处理器
    $handleRetry = fn() => false;
    $this->apple->shouldReceive('getHandleRetry')
        ->once()
        ->andReturn($handleRetry);

    $result = $this->appleConnector->handleRetry($exception, $request);
    expect($result)->toBeFalse();
});

/**
 * 测试日志记录功能
 */
test('test logging functionality', function () {
    // 模拟日志记录器
    $logger = Mockery::mock(LoggerInterface::class);
    $this->apple->shouldReceive('getLogger')
        ->once()
        ->andReturn($logger);

    // 验证日志记录器是否正确设置
    $result = $this->appleConnector->getLogger();
    expect($result)->toBe($logger);
});


/**
 * 测试响应格式化
 */
test('test response formatting', function () {
    // 模拟日志记录器
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('debug')
        ->once()
        ->with('response', Mockery::any());

    $this->apple->shouldReceive('getLogger')
        ->once()
        ->andReturn($logger);

    // 模拟响应对象
    $response = Mockery::mock(\Saloon\Http\Response::class);
    $response->shouldReceive('status')->andReturn(200);
    $response->shouldReceive('headers->all')->andReturn([]);
    $response->shouldReceive('body')->andReturn('test response');

    // 使用反射来调用protected方法
    $method = new ReflectionMethod($this->appleConnector, 'formatResponseLog');
    $result = $method->invoke($this->appleConnector, $response);

    expect($result)->toBe($response);
});

/**
 * 测试Cookie管理
 */
test('test cookie jar management', function () {
    // 模拟Cookie管理器
    $cookieJar = Mockery::mock(CookieJarInterface::class);

    $this->apple->shouldReceive('getCookieJar')
        ->once()
        ->andReturn($cookieJar);

    $result = $this->appleConnector->getCookieJar();
    expect($result)->toBe($cookieJar);
});

/**
 * 测试请求头同步功能
 */
test('test header synchronization', function () {
    // 模拟请求头同步接口
    $headerSync = Mockery::mock(HeaderSynchronizeInterface::class);

    $this->apple->shouldReceive('getHeaderRepositories')
        ->once()
        ->andReturn($headerSync);

    $result = $this->appleConnector->getHeaderRepositories();
    expect($result)->toBe($headerSync);
});

/**
 * 测试请求日志格式化
 */
test('test request logging format', function () {
    // 模拟日志记录器
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('debug')
        ->once()
        ->with('request', Mockery::any());

    $this->apple->shouldReceive('getLogger')
        ->once()
        ->andReturn($logger);

    // 模拟请求对象
    $pendingRequest = Mockery::mock(PendingRequest::class);
    $pendingRequest->shouldReceive('getMethod')->andReturn(Saloon\Enums\Method::POST);
    $pendingRequest->shouldReceive('getUri')->andReturn(Mockery::mock(\Psr\Http\Message\UriInterface::class));
    $pendingRequest->shouldReceive('config->all')->andReturn(['timeout' => 30]);
    $pendingRequest->shouldReceive('headers->all')->andReturn(['Content-Type' => 'application/json']);
    $pendingRequest->shouldReceive('body->all')->andReturn(['key' => 'value']);

    $result = $this->appleConnector->exposeFormatRequestLog($pendingRequest);
    expect($result)->toBe($pendingRequest);
});

/**
 * 测试空代理服务情况
 */
test('test null proxy service', function () {
    $this->apple->shouldReceive('getProxy')
        ->once()
        ->andReturnNull();

    $result = $this->appleConnector->getProxy();
    expect($result)->toBeNull();
});

/**
 * 测试空日志记录器情况
 */
test('test null logger', function () {
    $this->apple->shouldReceive('getLogger')
        ->once()
        ->andReturnNull();

    $result = $this->appleConnector->getLogger();
    expect($result)->toBeNull();
});

/**
 * 测试空Cookie管理器情况
 */
test('test null cookie jar', function () {
    $this->apple->shouldReceive('getCookieJar')
        ->once()
        ->andReturnNull();

    $result = $this->appleConnector->getCookieJar();
    expect($result)->toBeNull();
});

/**
 * 测试空请求头同步接口情况
 */
test('test null header repositories', function () {
    $this->apple->shouldReceive('getHeaderRepositories')
        ->once()
        ->andReturnNull();

    $result = $this->appleConnector->getHeaderRepositories();
    expect($result)->toBeNull();
});

/**
 * 测试基础URL解析
 */
test('test base url resolution', function () {
    $baseUrl = $this->appleConnector->resolveBaseUrl();
    expect($baseUrl)->toBe('https://test.example.com');
});

/**
 * 测试默认配置为空的情况
 */
test('test empty default config', function () {
    $this->apple->shouldReceive('config->all')
        ->once()
        ->andReturn([]);

    $result = $this->appleConnector->exposeDefaultConfig();
    expect($result)->toBeArray()
        ->toBeEmpty();
});

/**
 * 测试重试机制的默认处理器
 */
test('test default retry handler', function () {
    $exception = Mockery::mock(RequestException::class);
    $request   = Mockery::mock(Request::class);

    $this->apple->shouldReceive('getHandleRetry')
        ->once()
        ->andReturnNull();

    $result = $this->appleConnector->handleRetry($exception, $request);
    expect($result)->toBeTrue();
});

