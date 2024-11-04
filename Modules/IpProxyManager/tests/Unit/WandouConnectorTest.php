<?php

namespace Tests\Feature\Services\WandouProxy;

use App\Models\ProxyConfiguration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use InvalidArgumentException;
use Modules\IpProxyManager\Service\ProxyFactory;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\Wandou\Dto\AccountPasswordDto;
use Modules\IpProxyManager\Service\Wandou\Dto\DynamicDto;
use Modules\IpProxyManager\Service\Wandou\Request\AccountPasswordRequest;
use Modules\IpProxyManager\Service\Wandou\Request\DynamicRequest;
use Modules\IpProxyManager\Service\Wandou\WandouConnector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

// 响应构建器

class ProxyResponseBuilder
{
    protected array $data = [];

    public static function fake(): self
    {
        return new self();
    }

    public function withSuccess(bool $multiple = false): self
    {
        $baseData = [
            'ip'          => fake()->ipv4,
            'port'        => fake()->numberBetween(1000, 65535),
            'expire_time' => now()->addHour()->toDateTimeString(),
            'city'        => fake()->city,
            'isp'         => fake()->randomElement(['电信', '联通', '移动']),
        ];

        $this->data = [
            'code' => 200,
            'msg'  => 'success',
            'data' => $multiple ? [$baseData, array_merge($baseData, ['ip' => fake()->ipv4])] : [$baseData],
        ];

        return $this;
    }

    public function withError(string $type): self
    {
        $errors = [
            'invalid_key' => [
                'code'   => 20001,
                'msg'    => 'Invalid app_key',
                'status' => 401,
            ],
            'no_package'  => [
                'code'   => 10048,
                'msg'    => 'No available package',
                'status' => 403,
            ],
            'rate_limit'  => [
                'code'   => 10001,
                'msg'    => 'Rate limit exceeded',
                'status' => 429,
            ],
        ];

        $this->data = array_merge($errors[$type] ?? [], ['data' => []]);

        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}

// 测试助手类
class WandouTestHelper
{
    public static function createConfig(string $mode, array $additionalConfig = []): ProxyConfiguration
    {
        return ProxyConfiguration::factory()->create([
            'configuration'     => [
                'default_driver' => 'wandou',
                'wandou'         => array_merge([
                    'mode'    => $mode,
                    'app_key' => $mode === 'flow' ? 'test_key' : null,
                    'session' => $mode === 'dynamic' ? 'test_session' : null,
                ], $additionalConfig),
            ],
            'is_active'         => true,
            'ipaddress_enabled' => false,
            'proxy_enabled'     => false,
        ]);
    }

    public static function createMockSequence(array $responses): callable
    {
        $index = 0;

        return function () use (&$index, $responses) {
            return MockResponse::make($responses[$index++ % count($responses)]);
        };
    }

    public static function assertProxyResponse(?ProxyResponse $proxy): void
    {
        expect($proxy)
            ->toBeInstanceOf(ProxyResponse::class)
            ->and($proxy->host)->not->toBeEmpty()
            ->and($proxy->port)->toBeInt()->toBeGreaterThan(0)
            ->and($proxy->expireTime)->toBeInstanceOf(Carbon::class);
    }
}


uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(fn() => MockClient::destroyGlobal())
    ->in(__DIR__);

// 3. 按功能分组测试
describe('Wandou Proxy Base Components', function () {
    test('connector configuration is correct', function () {
        $connector = new WandouConnector();
        expect($connector)
            ->resolveBaseUrl()->toBe('https://api.wandouapp.com/')
            ->and($connector->tries)->toBe(5);
    });

    test('dtos handle values correctly', function () {
        // Account Password DTO
        $defaultAccountDto = new AccountPasswordDto();
        expect($defaultAccountDto->toQueryParameters())
            ->toHaveKey('num', 1)
            ->toHaveKey('xy', 1);

        $customAccountDto = new AccountPasswordDto(['app_key' => 'test', 'num' => 5]);
        expect($customAccountDto->toQueryParameters())
            ->toHaveKey('app_key', 'test')
            ->toHaveKey('num', 5);

        // Dynamic DTO
        $defaultDynamicDto = new DynamicDto();
        expect($defaultDynamicDto->toQueryParameters())
            ->toHaveKey('life', 1)
            ->toHaveKey('isp', 0);

        $customDynamicDto = new DynamicDto(['session' => 'test', 'life' => 5]);
        expect($customDynamicDto->toQueryParameters())
            ->toHaveKey('session', 'test')
            ->toHaveKey('life', 5);
    });
});

describe('Wandou Proxy Requests', function () {
    test('requests validate required parameters', function () {
        expect(fn() => new AccountPasswordRequest(new AccountPasswordDto()))
            ->toThrow(InvalidArgumentException::class, '请配置代理 app_key')
            ->and(fn() => new DynamicRequest(new DynamicDto()))
            ->toThrow(InvalidArgumentException::class, '请配置代理 session');

    });

    test('requests handle responses correctly', function () {
        // Success case
        $mockClient = MockClient::global([
            AccountPasswordRequest::class => MockResponse::make(
                ProxyResponseBuilder::fake()->withSuccess()->build()
            ),
        ]);

        $service = app(ProxyFactory::class)->create(
            WandouTestHelper::createConfig('flow')
        );

        $proxy = $service->fetchProxyFirst();
        WandouTestHelper::assertProxyResponse($proxy);

        MockClient::destroyGlobal();

        // Error case
        $mockClient = MockClient::global([
            AccountPasswordRequest::class => MockResponse::make(
                ProxyResponseBuilder::fake()->withError('invalid_key')->build()
            ),
        ]);

        $service = app(ProxyFactory::class)->create(
            WandouTestHelper::createConfig('flow')
        );

        expect($service->fetchProxyFirst())->toBeNull();
    });
});

describe('Wandou Proxy Service Features', function () {
    test('proxy refresh works correctly', function () {
        $mockClient = MockClient::global([
            '*' => WandouTestHelper::createMockSequence([
                ProxyResponseBuilder::fake()->withSuccess()->build(),
                ProxyResponseBuilder::fake()->withSuccess()->build(),
            ]),
        ]);

        $service = app(ProxyFactory::class)->create(
            WandouTestHelper::createConfig('flow')
        );

        $firstProxy     = $service->getProxy();
        $refreshedProxy = $service->refreshProxy();

        WandouTestHelper::assertProxyResponse($firstProxy);
        WandouTestHelper::assertProxyResponse($refreshedProxy);
        expect($firstProxy?->host)->not->toBe($refreshedProxy?->host);
    });

    test('mode switching works correctly', function () {
        $mockClient = MockClient::global([
            AccountPasswordRequest::class => MockResponse::make(
                ProxyResponseBuilder::fake()->withSuccess()->build()
            ),
            DynamicRequest::class         => MockResponse::make(
                ProxyResponseBuilder::fake()->withSuccess()->build()
            ),
        ]);

        // Test flow mode
        $flowService = app(ProxyFactory::class)->create(
            WandouTestHelper::createConfig('flow')
        );
        WandouTestHelper::assertProxyResponse($flowService->fetchProxyFirst());

        // Test dynamic mode
        $dynamicService = app(ProxyFactory::class)->create(
            WandouTestHelper::createConfig('dynamic')
        );
        WandouTestHelper::assertProxyResponse($dynamicService->fetchProxyFirst());
    });
});

describe('Wandou Proxy Error Handling', function () {
    test('service handles retries correctly', function () {
        $mockClient = MockClient::global([
            '*' => WandouTestHelper::createMockSequence([
                ProxyResponseBuilder::fake()->withError('rate_limit')->build(),
                ProxyResponseBuilder::fake()->withSuccess()->build(),
            ]),
        ]);

        $service = app(ProxyFactory::class)->create(
            WandouTestHelper::createConfig('flow')
        );

        expect($service->fetchProxyFirst())->toBeNull();
        WandouTestHelper::assertProxyResponse($service->fetchProxyFirst());
    });
});
