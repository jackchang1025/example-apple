<?php

namespace Tests\Unit;


use App\Apple\WebAnalytics\OnlineUsersTrait;
use Illuminate\Support\Collection;
use Mockery;

// 创建一个使用 OnlineUsersTrait 的测试类
class TestClass
{
    use OnlineUsersTrait;

    public function getOnlineAllPages(): Collection
    {
        return collect([
            'signin_page'     => collect(['user1' => time(), 'user2' => time()]),
            'verify_account'  => collect(['user1' => time(), 'user3' => time()]),
            'auth_phone_list' => collect(['user4' => time(), 'user5' => time()]),
        ]);
    }
}

beforeEach(function () {
    $this->testClass = Mockery::mock(TestClass::class)->makePartial();
});

it('可以获取特定路由的在线用户数量', function () {

    $result = $this->testClass->getOnlineCountForRoute('验证账号');

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(5)->toBeInt();
});

it('可以处理空路由情况', function () {
    $result = $this->testClass->getOnlineCountForRoute('不存在的路由');

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(0)->toBeInt();
});

it('可以获取多个路由的在线用户总数并去重', function () {
    $routeNames = ['验证账号', '授权'];

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('验证账号')->andReturn(collect(['user1' => 1726500278, 'user2' => 1726500216]))
        ->once();

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('授权')->andReturn(collect(['user1' => 1726500216, 'user3' => 1726500216]))
        ->once();

    $result = $this->testClass->getOnlineCountForAllRoutes($routeNames);

    expect($result->toArray())->toBe([
        "验证账号" => [
            "user2" => 1726500216,
        ],
        "授权"     => [
            "user1" => 1726500216,
            "user3" => 1726500216,
        ],
    ]);
});

it('获取多个路由的在线用户总数并去重', function () {
    $routeNames = ['验证账号', '授权'];

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('验证账号')->andReturn(collect(['user1' => 1726500278, 'user2' => 1726500216]))
        ->once();

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('授权')->andReturn(collect(['user1' => 1726500216, 'user2' => 1726500216]))
        ->once();

    $result = $this->testClass->getOnlineCountForAllRoutes($routeNames);

    expect($result->toArray())->toBe([
        "验证账号" => [
        ],
        "授权"     => [
            "user1" => 1726500216,
            "user2" => 1726500216,
        ],
    ]);
});

it('处理空数组输入', function () {

    $result = $this->testClass->getOnlineCountForAllRoutes([]);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBe(0)->toBeInt();
});

it('处理单个路由输入', function () {
    $routeNames = ['首页'];

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('首页')
        ->andReturn(collect(['user1' => 1726500278, 'user2' => 1726500216]))
        ->once();

    $result = $this->testClass->getOnlineCountForAllRoutes($routeNames);

    expect($result)->toBeInstanceOf(Collection::class)->and($result->toArray())->toBe([
        "首页" => [
            "user1" => 1726500278,
            "user2" => 1726500216,
        ],
    ]);
});


it('处理重复的路由名称', function () {
    $routeNames = ['验证账号', '验证账号', '授权'];

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('验证账号')
        ->andReturn(collect(['user1' => 1726500278, 'user2' => 1726500216]))
        ->once();

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('授权')
        ->andReturn(collect(['user2' => 17265002784, 'user3' => 1726500216]))
        ->once();

    $result = $this->testClass->getOnlineCountForAllRoutes($routeNames);

    expect($result)->toBeInstanceOf(Collection::class)->and($result->toArray())->toBe([
        "验证账号" => [
            "user1" => 1726500278,
        ],
        "授权"     => [

            "user2" => 17265002784,
            "user3" => 1726500216,
        ],
    ]);
});

it('处理不存在的路由名称', function () {
    $routeNames = ['不存在的路由', '验证账号'];

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('不存在的路由')
        ->andReturn(collect([]))
        ->once();

    $this->testClass->shouldReceive('getOnlineCountForRoute')
        ->with('验证账号')
        ->andReturn(collect(['user1' => 1726500278, 'user2' => 1726500216]))
        ->once();

    $result = $this->testClass->getOnlineCountForAllRoutes($routeNames);

    expect($result)->toBeInstanceOf(Collection::class)->and($result->toArray())->toBe([
        '不存在的路由' => [],
        "验证账号"     => [
            "user1" => 1726500278,
            "user2" => 1726500216,
        ],
    ]);
});



