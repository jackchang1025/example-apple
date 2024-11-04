<?php


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\ClientBuilder;
use Modules\AppleClient\Service\Integrations\Request;
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

uses(TestCase::class);
uses(RefreshDatabase::class);
uses()
    ->beforeEach(fn() => MockClient::destroyGlobal())
    ->in(__DIR__);


beforeEach(function () {
    $this->logger       = Mockery::mock(LoggerInterface::class);
    $this->cache        = app(CacheInterface::class);
    $this->proxyService = Mockery::mock(ProxyService::class);
    $this->appleClient  = new AppleClient();
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

    // Create client factory
    $factory = new ClientBuilder(
        $this->appleClient,
        $this->logger,
        $this->cache,
        $this->proxyService
    );

    // Configure mock responses to simulate connection failures
    $mockClient = new MockClient([
        MockResponse::make(['error' => 'Connection timeout'], 408),
        MockResponse::make(['error' => 'Connection timeout'], 408),
        MockResponse::make(['success' => true], 200),
    ]);

    // Get configured client
    $client = $factory->getClient('test-session')
        ->withTries(3)
        ->withRetryInterval(0)
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
    $client->getIdmsaConnector()->send($request, $mockClient);

    // Verify that different proxies were used in each attempt
    expect($capturedConfigs)
        ->toHaveCount(3)
        ->and($capturedConfigs[0])->toBe('http://1.1.1.1:8080')
        ->and($capturedConfigs[1])->toBe('http://2.2.2.2:8080')
        ->and($capturedConfigs[2])->toBe('http://3.3.3.3:8080');
});

test('proxy refresh should be triggered on connection exception log ', function () {

    \App\Models\ProxyConfiguration::factory()->create([
        'name'              => 'test',
        'configuration'     => [
            'huashengdaili'  => [
                'mode'    => 'api',
                'session' => 'U7a7284e90723194213--qXYnoeRnl94IOWQ',
            ],
            'default_driver' => 'huashengdaili',
        ],
        'is_active'         => 1,
        'ipaddress_enabled' => 0,
        'proxy_enabled'     => 0,
    ]);

    // Configure proxy service mock
    /**
     * @var \Modules\IpProxyManager\Service\ProxyService $this ->proxyService
     */
    $this->proxyService = app(ProxyService::class);
    $this->proxyService->enableProxy(true);

    // Create client factory
    $factory = new ClientBuilder(
        $this->appleClient,
        app(LoggerInterface::class),
        $this->cache,
        $this->proxyService
    );

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

    $this->proxyService->getConnector()->withMockClient($proxyServiceMockClient);


    // Get configured client
    $client = $factory->getClient('test-session')
        ->withHandleRetry(function (FatalRequestException|RequestException $exception, Request $request) {

            if (($exception instanceof FatalRequestException || $exception instanceof RequestTimeOutException) && $this->proxyService->isProxyEnabled(
                )) {
                $this->proxyService->refreshProxy();

                return true;
            }

            return false;
        });

    $capturedConfigs = [];

    $client->middleware()->onRequest(function (PendingRequest $request) use (&$capturedConfigs) {

        $capturedConfigs[] = $request->config()->get('proxy');

    });

    $client->getIdmsaConnector()->tries = 4;
    $client->getIdmsaConnector()->withMockClient($mockClient);

    // Send request with mock client
    $client->getIdmsaConnector()->auth();

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
        ->once()
        ->andReturn($initialProxy);

    $this->logger
        ->shouldReceive('debug')->times(2);

    // Create client factory
    $factory = new ClientBuilder(
        $this->appleClient,
        $this->logger,
        $this->cache,
        $this->proxyService
    );

    $client = $factory->getClient('test-session');

    // Capture initial config
    $request = new class extends Request {

        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    $pendingRequest     = $client->getIdmsaConnector()->createPendingRequest($request);
    $initialProxyConfig = $pendingRequest->config()->get('proxy');

    // Simulate proxy refresh
    $this->proxyService
        ->shouldReceive('refreshProxy')
        ->once()
        ->andReturn($newProxy);

    $this->proxyService
        ->shouldReceive('getProxy')
        ->once()
        ->andReturn($newProxy);

    // Force proxy refresh
    $client->getProxy()?->refreshProxy();

    // Create new request to check updated config
    $newRequest = new class extends Request {
        protected Method $method = Method::POST;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    $newPendingRequest  = $client->getIdmsaConnector()->createPendingRequest($newRequest);
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

    $factory = new ClientBuilder(
        $this->appleClient,
        $this->logger,
        $this->cache,
        $this->proxyService
    );

    $client = $factory->getClient('test-session');

    $pendingRequest = Mockery::mock(PendingRequest::class);

    $exception = new FatalRequestException(
        new Exception('GET', 500),
        $pendingRequest
    );

    // Simulate retry handler
    $handleRetry = $client->getHandleRetry();
    $result      = $handleRetry($exception, Mockery::mock(Request::class));

    expect($result)->toBeTrue();
});

