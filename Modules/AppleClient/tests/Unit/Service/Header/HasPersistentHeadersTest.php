<?php

namespace Modules\AppleClient\Tests\Unit\Service\Header;

use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Modules\AppleClient\Service\Header\HasPersistentHeaders;
use Saloon\Contracts\ArrayStore as ArrayStoreContract;
use Saloon\Repositories\ArrayStore;
use Mockery\MockInterface;

uses(TestCase::class);

// 修改测试类名
class PersistentHeadersTestClass
{
    use HasPersistentHeaders;

    // 重写默认持久化头部方法用于测试
    public function defaultPersistentHeaders(): array
    {
        return ['X-Default' => 'default-value'];
    }
}

beforeEach(function () {
    $this->testClass = new PersistentHeadersTestClass();
});

// 测试设置持久化头部
it('测试设置持久化头部', function () {
    /** @var MockInterface&ArrayStoreContract */
    $store = Mockery::mock(ArrayStoreContract::class);

    $this->testClass->withPersistentHeaders($store);

    expect($this->testClass->getPersistentHeaders())->toBe($store);
});

// 测试获取默认持久化头部
it('测试获取默认持久化头部', function () {
    $headers = $this->testClass->getPersistentHeaders();

    expect($headers)
        ->toBeInstanceOf(ArrayStore::class)
        ->and($headers->all())->toBe(['X-Default' => 'default-value']);
});

// 测试自定义默认头部
it('测试自定义默认头部', function () {
    $customClass = new class extends PersistentHeadersTestClass {
        public function defaultPersistentHeaders(): array
        {
            return ['X-Custom' => 'custom-value'];
        }
    };

    expect($customClass->getPersistentHeaders()->all())
        ->toBe(['X-Custom' => 'custom-value']);
});
