<?php


use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Integrations\Request;
use Modules\IpProxyManager\Service\ProxyConnector;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\Request\Statuses\RequestTimeOutException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Config\Config;
use Modules\IpProxyManager\Service\BaseDto;
use Modules\IpProxyManager\Service\Request as ProxyRequest;

uses(TestCase::class);
uses()
    ->beforeEach(fn() => MockClient::destroyGlobal())
    ->in(__DIR__);


beforeEach(function () {

    $this->account      = new Account('test@example.com', 'test-password');
    $this->logger       = Mockery::mock(LoggerInterface::class);
    $this->cache        = app(CacheInterface::class);
    $this->proxyService = Mockery::mock(ProxyService::class);

    $this->config = new Config();
    $this->apple  = new Apple($this->account, $this->config);
});

test('proxy should be updated during retries', function () {
    // Mock responses for proxy service
    $proxies = [
        new ProxyResponse(host: '1.1.1.1', port: '8080', url: "http://1.1.1.1:8080"),
        new ProxyResponse(host: '2.2.2.2', port: '8080', url: "http://2.2.2.2:8080"),
        new ProxyResponse(host: '3.3.3.3', port: '8080', url: "http://3.3.3.3:8080"),
    ];

    $currentProxyIndex = 0;

    // Configure proxy service mock
    $this->proxyService
        ->shouldReceive('isProxyEnabled')
        ->andReturn(true);

    // getProxy 方法在 bootHasProxy 中调用
    $this->proxyService
        ->shouldReceive('getProxy')
        ->andReturnUsing(function () use ($proxies, &$currentProxyIndex) {
            return $proxies[$currentProxyIndex];
        });

    // refreshProxy 方法在 handleRetry 中调用
    $this->proxyService
        ->shouldReceive('refreshProxy')
        ->times(2) // 期望调用两次
        ->andReturnUsing(function () use ($proxies, &$currentProxyIndex) {
            $currentProxyIndex++;

            return $proxies[$currentProxyIndex];
        });

    // Configure logger mock to capture config
    $capturedConfigs = [];
    $this->logger
        ->shouldReceive('debug')
        ->withArgs(function ($message, $context) use (&$capturedConfigs) {
            if ($message === 'request') {
                $capturedConfigs[] = $context['config']['proxy'] ?? null;
            }

            return true;
        });

    // Configure mock responses to simulate connection failures
    $mockClient = new MockClient([
        MockResponse::make(['error' => 'Connection timeout'], 408),
        MockResponse::make(['error' => 'Connection timeout'], 408),
        MockResponse::make(['success' => true], 200),
    ]);

    // Get configured client
    $this->apple
        ->withLogger($this->logger)
        ->withMockClient($mockClient)
        ->withTries(3)
        ->withRetryInterval(0)
        ->withProxy($this->proxyService)
        ->withHandleRetry(function (FatalRequestException|RequestException $exception, Request $request) {

            if (($exception instanceof FatalRequestException || $exception instanceof RequestTimeOutException) && $this->proxyService->isProxyEnabled(
                )) {
                $this->proxyService->refreshProxy();

                return true;
            }

            return false;
        });

    // Create test request
    $request = new class extends Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    // Send request with mock client
    $this->apple->getWebResource()->getIdmsaResource()->getIdmsaConnector()->send($request, $mockClient);

    // Verify that different proxies were used in each attempt
    expect($capturedConfigs)
        ->toHaveCount(3)
        ->and($capturedConfigs[0])->toBe('http://1.1.1.1:8080')
        ->and($capturedConfigs[1])->toBe('http://2.2.2.2:8080')
        ->and($capturedConfigs[2])->toBe('http://3.3.3.3:8080');
});

test('proxy refresh should be triggered on connection exception log ', function () {
    /** @var LoggerInterface $logger */
    $logger = Mockery::mock(LoggerInterface::class)->makePartial();

    // 创建一个具体的 ProxyConnector 实现
    $connector = new class extends ProxyConnector {
        public function resolveBaseUrl(): string
        {
            return 'https://example.com';
        }

        protected function defaultHeaders(): array
        {
            return [];
        }
    };

    // 创建一个具体的 BaseDto 实现
    $dto = new class([
        'proxy_enabled'     => true,
        'ipaddress_enabled' => false,
    ]) extends BaseDto {
        public function toQueryParameters(): array
        {
            return $this->all();
        }
    };

    // 创建一个具体的 Request 实现
    $request = new class($dto) extends \Modules\IpProxyManager\Service\Request {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    // 使用 mock 而不是真实实例
    $this->proxyService = Mockery::mock(ProxyService::class);
    $this->proxyService->shouldReceive('isProxyEnabled')->andReturn(true);

    // 模拟 getProxy 方法
    $proxies = [
        new ProxyResponse(host: '1.1.1.1', port: '8080', url: "http://1.1.1.1:8080"),
        new ProxyResponse(host: '2.2.2.2', port: '8080', url: "http://2.2.2.2:8080"),
        new ProxyResponse(host: '3.3.3.3', port: '8080', url: "http://3.3.3.3:8080"),
    ];

    $currentProxyIndex = 0;

    $this->proxyService->shouldReceive('getProxy')
        ->andReturnUsing(function () use ($proxies, &$currentProxyIndex) {
            return $proxies[$currentProxyIndex];
        });

    $this->proxyService->shouldReceive('refreshProxy')
        ->andReturnUsing(function () use ($proxies, &$currentProxyIndex) {
            $currentProxyIndex++;

            return $proxies[$currentProxyIndex];
        });

    // Configure mock responses to simulate connection failures
    $mockClient = new MockClient([
        MockResponse::make(['error' => 'Connection timeout'], 408),
        MockResponse::make(['error' => 'Connection timeout'], 408),
        MockResponse::make(['success' => true], 200),
    ]);

    $proxyServiceMockClient = new MockClient([
        MockResponse::make([
            'status' => '0',
            'list'   => [
                [
                    'sever' => '1.1.1.1',
                    'port'  => '8080',
                ],
            ],
        ]),
        MockResponse::make([
            'status' => '0',
            'list'   => [
                [
                    'sever' => '2.2.2.2',
                    'port'  => '8080',
                ],
            ],
        ]),
        MockResponse::make([
            'status' => '0',
            'list'   => [
                [
                    'sever' => '3.3.3.3',
                    'port'  => '8080',
                ],
            ],
        ]),
    ]);

    $connector->withMockClient($proxyServiceMockClient);

    // 设置 mock 的行为
    $this->proxyService->shouldReceive('getConnector')
        ->andReturn($connector);

    $capturedConfigs = [];

    // Get configured client
    $this->apple
        ->withProxy($this->proxyService)
        ->withTries(4)
        ->withMockClient($mockClient)
        ->withHandleRetry(function (FatalRequestException|RequestException $exception, Request $request) {
            if (($exception instanceof FatalRequestException || $exception instanceof RequestTimeOutException) && $this->proxyService->isProxyEnabled(
                )) {
                $this->proxyService->refreshProxy();

                return true;
            }

            return false;
        })->middleware()->onRequest(function (PendingRequest $request) use (&$capturedConfigs) {
            $capturedConfigs[] = $request->config()->get('proxy');
        });

    $request = new class extends Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    // Send request with mock client
    $this->apple->getWebResource()->getIdmsaResource()->getIdmsaConnector()->send($request);

    // Verify that different proxies were used in each attempt

    expect(array_unique($capturedConfigs))
        ->toHaveCount(3);
});


test('should correctly update config after proxy refresh', function () {
    // Mock initial proxy
    $initialProxy = new ProxyResponse(host: '1.1.1.1', port: '8080', url: "http://1.1.1.1:8080");
    $newProxy     = new ProxyResponse(host: '2.2.2.2', port: '8080', url: "http://2.2.2.2:8080");

    $this->proxyService
        ->shouldReceive('isProxyEnabled')
        ->andReturn(true);

    $this->proxyService
        ->shouldReceive('getProxy')
        ->twice()
        ->andReturn($initialProxy, $newProxy);

    // 创建 mock client
    $mockClient = new MockClient([
        MockResponse::make(['success' => true], 200),
        MockResponse::make(['success' => true], 200),
    ]);

    // 先设置代理服务和 mock client
    $this->apple
        ->withProxy($this->proxyService)
        ->withMockClient($mockClient);

    // Capture initial config
    $request = new class extends Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    // 获取 connector
    $connector = $this->apple->getWebResource()
        ->getIdmsaResource()
        ->getIdmsaConnector();

    // 创建请求并获取配置
    $pendingRequest     = $connector->createPendingRequest($request);
    $initialProxyConfig = $pendingRequest->config()->get('proxy');

    // Simulate proxy refresh
    $this->proxyService
        ->shouldReceive('refreshProxy')
        ->once()
        ->andReturn($newProxy);

    // Force proxy refresh
    $this->apple->getProxy()?->refreshProxy();

    // Create new request to check updated config
    $newRequest = new class extends Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    // 创建新请求并获取配置
    $newPendingRequest  = $connector->createPendingRequest($newRequest);
    $updatedProxyConfig = $newPendingRequest->config()->get('proxy');

    // Verify proxy was updated
    expect($initialProxyConfig)->toBe('http://1.1.1.1:8080')
        ->and($updatedProxyConfig)->toBe('http://2.2.2.2:8080');
});

test('proxy refresh should be triggered on connection exception', function () {
    $this->proxyService
        ->shouldReceive('isProxyEnabled')
        ->andReturn(true);

    $this->proxyService
        ->shouldReceive('refreshProxy')
        ->once()
        ->andReturn(new ProxyResponse(host: '2.2.2.2', port: '8080', url: "http://2.2.2.2:8080"));

    // 创建一个真实的 PendingRequest 实例
    $pendingRequest = new PendingRequest(
        $this->apple->getWebResource()->getIdmsaResource()->getIdmsaConnector(),
        new class extends Request {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/test';
            }
        }
    );

    $exception = new FatalRequestException(
        new Exception('GET', 500),
        $pendingRequest
    );

    // 设置重试处理器
    $this->apple
        ->withProxy($this->proxyService)
        ->withHandleRetry(function (FatalRequestException|RequestException $exception, Request $request) {
            if (($exception instanceof FatalRequestException || $exception instanceof RequestTimeOutException)
                && $this->proxyService->isProxyEnabled()
            ) {
                $this->proxyService->refreshProxy();

                return true;
            }

            return false;
        });

    // 获取并执行重试处理器
    $handleRetry = $this->apple->getHandleRetry();
    $result      = $handleRetry($exception, Mockery::mock(Request::class));

    expect($result)->toBeTrue();
});

