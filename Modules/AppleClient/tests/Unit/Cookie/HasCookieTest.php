<?php

use Modules\AppleClient\Service\Cookies\CookieJar;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Saloon\Helpers\MiddlewarePipeline;
use Saloon\Http\PendingRequest;

class DummyClass
{
    use HasCookie;
}

// 测试设置 null
test('withCookies sets cookieJar to null when given null', function () {
    $dummy = new DummyClass();
    $dummy->withCookieJar(null);
    expect($dummy->getCookieJar())->toBeNull();
});

// 测试设置 CookieJarInterface 实例
test('withCookies sets cookieJar when given a CookieJarInterface instance', function () {
    $dummy     = new DummyClass();
    $cookieJar = new CookieJar();
    $dummy->withCookieJar($cookieJar);
    expect($dummy->getCookieJar())->toBe($cookieJar);
});

// 测试设置数组
test('withCookies creates a CookieJar from array', function () {
    $dummy   = new DummyClass();
    $cookies = [
        [
            'Name'   => 'test_cookie',
            'Value'  => 'test_value',
            'Domain' => 'example.com',
        ],
    ];
    $dummy->withCookieJar($cookies);
    $cookieJar = $dummy->getCookieJar();

    expect($cookieJar)->toBeInstanceOf(CookieJar::class)
        ->and($cookieJar?->getCookieByName('test_cookie')?->getValue())->toBe('test_value');
});

// 测试 strictMode
test('withCookies respects strictMode parameter', function () {
    $dummy   = new DummyClass();
    $cookies = [
        [
            'Name'   => 'test_cookie',
            'Value'  => 'test_value',
            'Domain' => 'example.com',
        ],
    ];
    $dummy->withCookieJar($cookies, true);
    $cookieJar = $dummy->getCookieJar();

    expect($cookieJar)->toBeInstanceOf(CookieJar::class)
        ->and($cookieJar?->toArray()[0]['Discard'])->toBeFalse();
});

// 测试 bootHasCookie 方法
test('bootHasCookie adds middleware correctly', function () {
    $dummy = new DummyClass();

    $middleware = Mockery::mock(MiddlewarePipeline::class);
    $middleware->shouldReceive('getRequestPipeline->getPipes')->once()->andReturn([]);
    $middleware->shouldReceive('getResponsePipeline->getPipes')->once()->andReturn([]);

    $pendingRequest = Mockery::mock(PendingRequest::class);
    $connector      = Mockery::mock(\Saloon\Http\Connector::class);

    $pendingRequest->shouldReceive('getConnector')->andReturn($connector);
    $connector->shouldReceive('middleware')->andReturn($middleware);
    $middleware->shouldReceive('onRequest')->once();
    $middleware->shouldReceive('onResponse')->once();

    $dummy->bootHasCookie($pendingRequest);
});

// 测试 CookieJar 的行为
test('CookieJar correctly handles cookies', function () {
    $dummy   = new DummyClass();
    $cookies = [
        [
            'Name'   => 'session',
            'Value'  => 'abc123',
            'Domain' => 'example.com',
        ],
        [
            'Name'   => 'user',
            'Value'  => 'john_doe',
            'Domain' => 'example.com',
        ],
    ];
    $dummy->withCookieJar($cookies);
    $cookieJar = $dummy->getCookieJar();

    expect($cookieJar->getCookieByName('session')->getValue())->toBe('abc123')
        ->and($cookieJar->getCookieByName('user')->getValue())->toBe('john_doe');
});

// 测试覆盖现有的 cookie
test('withCookies overwrites existing cookies', function () {
    $dummy = new DummyClass();
    $dummy->withCookieJar(
        $cookies = [
            [
                'Name'   => 'existing',
                'Value'  => 'old_value',
                'Domain' => 'example.com',
            ],
        ]
    );

    $dummy->withCookieJar(
        $cookies = [
            [
                'Name'   => 'existing',
                'Value'  => 'new_value',
                'Domain' => 'example.com',
            ],
        ]
    );

    expect($dummy->getCookieJar()->getCookieByName('existing')->getValue())->toBe('new_value');
});

// 测试合并新旧 cookie
test('withCookies replaces entire CookieJar', function () {
    $dummy = new DummyClass();

    $dummy->withCookieJar(
        $cookies = [
            [
                'Name'   => 'existing',
                'Value'  => 'old_value',
                'Domain' => 'example.com',
            ],
        ]
    );

    $dummy->withCookieJar(
        $cookies = [
            [
                'Name'   => 'new',
                'Value'  => 'new_value',
                'Domain' => 'example.com',
            ],
        ]
    );

    $cookieJar = $dummy->getCookieJar();

    expect($cookieJar->getCookieByName('existing'))->toBeNull()
        ->and($cookieJar->getCookieByName('new')->getValue())->toBe('new_value');
});
