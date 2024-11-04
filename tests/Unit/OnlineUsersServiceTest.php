<?php

namespace Tests\Unit;

use App\Apple\WebAnalytics\OnlineUsersService;
use Illuminate\Container\Container;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->redis = Mockery::mock(RedisManager::class);
    $container   = Mockery::mock(Container::class);
    $container->shouldReceive('get')->with('redis')->andReturn($this->redis);
    $this->service = new OnlineUsersService($container);
});

it('记录用户访问并设置过期时间', function () {
    $uri       = '/home';
    $sessionId = 'testsessionid';
    $now       = time();

    $this->redis->shouldReceive('hSet')
        ->once()
        ->with('online_users:/home', $sessionId, Mockery::any())
        ->andReturn(1);

    $this->redis->shouldReceive('expire')
        ->once()
        ->with('online_users:/home', 60)
        ->andReturn(true);

    $result = $this->service->recordVisit($uri, $sessionId);

    expect($result)->toBe($sessionId);
});

it('获取特定页面的在线用户数量', function () {
    $uri   = '/dashboard';
    $now   = time();
    $users = [
        'session1' => $now - 30,
        'session2' => $now - 50,
        'session3' => $now - 70, // 超过阈值
    ];

    $this->redis->shouldReceive('hGetAll')
        ->once()
        ->with('online_users:/dashboard')
        ->andReturn($users);

    $this->service->setOnlineThreshold(60);

    $count = $this->service->getOnlineCount($uri);

    expect($count)->toBe(2);
});

it('获取总在线用户数量正确汇总', function () {
    $now  = time();
    $keys = ['online_users:/home', 'online_users:/dashboard'];

    $this->redis->shouldReceive('keys')
        ->once()
        ->with('online_users:*')
        ->andReturn($keys);

    $this->redis->shouldReceive('hGetAll')
        ->with('online_users:/home')
        ->andReturn([
            'session1' => $now - 10,
            'session2' => $now - 20,
        ]);

    $this->redis->shouldReceive('hGetAll')
        ->with('online_users:/dashboard')
        ->andReturn([
            'session3' => $now - 30,
            'session4' => $now - 70, // 超过阈值
        ]);

    $this->service->setOnlineThreshold(60);

    $total = $this->service->getTotalOnlineCount();

    expect($total)->toBe(3);
});

it('获取特定路由的在线用户数量，无效路由返回0', function () {
    $routeName = 'invalid_route';

    $count = $this->service->getOnlineForRoute($routeName);

    expect($count)->toBe(0);
});

it('获取所有页面的在线用户数量正确返回', function () {

    $now    = time();
    $prefix = config('database.redis.options.prefix', '');

    $keys = ['online_users:/home', 'online_users:/dashboard'];


    // 断言 keys 被正确调用（不带前缀）
    $this->redis->shouldReceive('keys')
        ->once()
        ->with('online_users:*')
        ->andReturn($keys);

    // 断言 hGetAll 被正确调用（带前缀）
    $this->redis->shouldReceive('hGetAll')
        ->with($prefix.'online_users:/home')
        ->andReturn([
            'session1' => $now - 10,
            'session2' => $now - 20,
        ]);

    $this->redis->shouldReceive('hGetAll')
        ->with($prefix.'online_users:/dashboard')
        ->andReturn([
            'session3' => $now - 30,
            'session4' => $now - 70, // 超过阈值
        ]);

    // 设置在线阈值
    $this->service->setOnlineThreshold(60);

    $result = $this->service->getOnlineAllPages();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(2)
        ->and($result->get('/home'))->toBe(2)
        ->and($result->get('/dashboard'))->toBe(1);
});
