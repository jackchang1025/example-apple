<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\leaveFamily\leaveFamily;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\LeaveFamilyRequest;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

beforeEach(function () {
    // 设置基础测试数据
    $this->appleId  = 'test@example.com';
    $this->password = 'testPassword123';

    // 创建请求实例
    $this->request = new LeaveFamilyRequest();

    // 创建 IcloudConnector 实例
    $this->icloudConnector = new IcloudConnector(
        new AppleClient(new Account($this->appleId, $this->password))
    );
});

// 测试请求基本属性
it('测试请求的基本属性', function () {
    expect(LeaveFamilyRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest();
});

// 测试请求端点
it('测试请求端点正确', function () {
    expect($this->request->resolveEndpoint())
        ->toBe('/setup/mac/family/leaveFamily');
});

// 测试成功离开家庭的场景
it('测试成功离开家庭场景', function () {
    $mockClient = new MockClient([
        LeaveFamilyRequest::class => MockResponse::make(
            body: '{
    "status-message": "加入家庭的此邀请不再有效。请要求家庭组织者重新添加你。",
    "isMemberOfFamily": false,
    "title": "家人共享邀请不再有效。",
    "status": 0
}'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($dto)
        ->toBeInstanceOf(leaveFamily::class)
        ->and($dto->status)->toBe(0)
        ->and($dto->statusMessage)->toBe('加入家庭的此邀请不再有效。请要求家庭组织者重新添加你。')
        ->and($dto->title)->toBe('家人共享邀请不再有效。')
        ->and($dto->isMemberOfFamily)->toBeFalse();
});


// 测试组织者尝试直接离开家庭的场景
it('测试组织者尝试直接离开家庭场景', function () {
    $mockClient = new MockClient([
        LeaveFamilyRequest::class => MockResponse::make(
            body: '{
                "status": 1,
                "status-message": "Family organizer cannot leave family directly",
                "title": "Action Not Allowed",
                "isMemberOfFamily": true
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($dto->status)->toBe(1)
        ->and($dto->statusMessage)->toBe('Family organizer cannot leave family directly')
        ->and($dto->title)->toBe('Action Not Allowed')
        ->and($dto->isMemberOfFamily)->toBeTrue();
});


// 测试服务器错误场景
it('测试服务器错误响应', function () {
    $mockClient = new MockClient([
        LeaveFamilyRequest::class => MockResponse::make(
            body: ['error' => 'Internal Server Error'],
            status: 500
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);

    expect(fn() => $this->icloudConnector->send($this->request))
        ->toThrow(RequestException::class);
});


// 测试响应 DTO 结构完整性
it('测试响应 DTO 结构完整性', function () {
    $mockClient = new MockClient([
        LeaveFamilyRequest::class => MockResponse::make(
            body: '{
                "status": 200,
                "status-message": "Test message",
                "title": "Test title",
                "isMemberOfFamily": false,
                "additional_field": "should be ignored"
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    // 验证 DTO 只包含定义的字段
    expect($dto)
        ->toBeInstanceOf(leaveFamily::class)
        ->and($dto)->toHaveProperties([
            'status',
            'statusMessage',
            'title',
            'isMemberOfFamily',
        ])
        ->and(fn() => $dto->additional_field)
        ->toThrow(ErrorException::class);
});
