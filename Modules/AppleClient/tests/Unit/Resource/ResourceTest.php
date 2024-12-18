<?php

use Modules\AppleClient\Service\Resources\Resource;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;

uses(TestCase::class);

/**
 * 用于测试的具体AppleConnector实现
 */
class TestResource extends Resource
{

}

beforeEach(function () {
    /** @var Apple $apple */
    $this->apple = Mockery::mock(Apple::class);

    // 模拟构造函数中需要的方法
    $this->testResource = new TestResource($this->apple);
});

test('test resource', function () {
    expect($this->testResource)->toBeInstanceOf(TestResource::class);
});

it('test get apple', function () {
    expect($this->testResource->getApple())->toBe($this->apple);
});
