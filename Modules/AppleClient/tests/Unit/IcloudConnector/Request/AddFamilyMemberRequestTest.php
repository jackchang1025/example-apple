<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\Family;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\AddFamilyMemberRequest;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Spatie\LaravelData\DataCollection;

uses(TestCase::class);

beforeEach(function () {
    // 设置基础测试数据
    $this->appleId  = 'test@example.com';
    $this->password = 'testPassword123';

    // 设置添加家庭成员的请求参数
    $this->memberAppleId       = 'member@example.com';
    $this->memberPassword      = 'memberPass123';
    $this->appleIdForPurchases = 'member@example.com';
    $this->verificationToken   = 'test-token-123';
    $this->preferredAppleId    = 'member@example.com';

    // 创建请求实例
    $this->request = new AddFamilyMemberRequest(
        $this->memberAppleId,
        $this->memberPassword,
        $this->appleIdForPurchases,
        $this->verificationToken,
        $this->preferredAppleId,
        true,
        true
    );

    // 创建 IcloudConnector 实例
    $this->icloudConnector = new IcloudConnector(
        new AppleClient(new Account($this->appleId, $this->password))
    );
});

// 测试请求基本属性
it('测试请求的基本属性', function () {
    expect(AddFamilyMemberRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();
});

// 测试请求端点
it('测试请求端点正确', function () {
    expect($this->request->resolveEndpoint())
        ->toBe('/setup/mac/family/addFamilyMember');
});

// 测试默认请求体
it('测试默认请求体内容', function () {
    $expectedBody = [
        'appleId'                        => $this->memberAppleId,
        'password'                       => $this->memberPassword,
        'appleIdForPurchases'            => $this->appleIdForPurchases,
        'shareMyLocationEnabledDefault'  => true,
        'shareMyPurchasesEnabledDefault' => true,
        'verificationToken'              => $this->verificationToken,
        'preferredAppleId'               => $this->preferredAppleId,
    ];

    expect($this->request->defaultBody())->toBe($expectedBody);
});

// 测试成功添加家庭成员
it('测试成功添加家庭成员场景', function () {
    $mockClient = new MockClient([
        AddFamilyMemberRequest::class => MockResponse::make(
            body: (file_get_contents(base_path('/Modules/AppleClient/tests/Unit/Files/AddFamilyMemberRequest.json')))
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($dto)
        ->toBeInstanceOf(FamilyInfo::class)
        ->and($dto->status)->toBe(0)
        ->and($dto->statusMessage)->toBe('Member of a family.')
        ->and($dto->isMemberOfFamily)->toBeTrue()
        ->and($dto->family)->toBeInstanceOf(Family::class)
        ->and($dto->familyMembers)->toBeInstanceOf(DataCollection::class)
        ->and($dto->family->familyId)->toBe('3038411018722656')
        ->and($dto->familyMembers->first()->fullName)->toBe('chang jack');
});

// 测试添加已存在的家庭成员
it('测试添加已存在的家庭成员场景', function () {
    $mockClient = new MockClient([
        AddFamilyMemberRequest::class => MockResponse::make(
            body: '{
    "status-message": "licade_2015@163.com 已经是另一个家庭的成员。账户一次只能加入一个家庭。",
    "title": "无法加入“家人共享”"
}'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($dto->status)->toBeNull()
        ->and($dto->statusMessage)->toBe('licade_2015@163.com 已经是另一个家庭的成员。账户一次只能加入一个家庭。')
        ->and($dto->title)->toBe('无法加入“家人共享”');
});

// 测试无效的验证令牌
it('测试无效的验证令牌场景', function () {
    $invalidRequest = new AddFamilyMemberRequest(
        $this->memberAppleId,
        $this->memberPassword,
        $this->appleIdForPurchases,
        'invalid-token',
        $this->preferredAppleId
    );

    $mockClient = new MockClient([
        AddFamilyMemberRequest::class => MockResponse::make(
            body: '{
                "status": 1,
                "status-message": "Invalid verification token",
                "title": "Verification Failed"
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($invalidRequest);
    $dto      = $response->dto();

    expect($dto->status)->toBe(1)
        ->and($dto->statusMessage)->toBe('Invalid verification token');
});

// 测试家庭成员数量超限
it('测试家庭成员数量超限场景', function () {
    $mockClient = new MockClient([
        AddFamilyMemberRequest::class => MockResponse::make(
            body: '{
                "status": 1,
                "status-message": "Family has reached maximum member limit",
                "title": "Family Full"
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($dto->status)->toBe(1)
        ->and($dto->statusMessage)->toBe('Family has reached maximum member limit')
        ->and($dto->title)->toBe('Family Full');
});

// 测试密码错误场景
it('测试密码错误场景', function () {
    $requestWithWrongPassword = new AddFamilyMemberRequest(
        $this->memberAppleId,
        'wrongPassword',
        $this->appleIdForPurchases,
        $this->verificationToken,
        $this->preferredAppleId
    );

    $mockClient = new MockClient([
        AddFamilyMemberRequest::class => MockResponse::make(
            body: '{
    "status-message": "你的 Apple ID 或密码不正确。",
    "title": "无法登录",
    "status": 1
}'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($requestWithWrongPassword);
    $dto      = $response->dto();

    expect($dto->status)->toBe(1)
        ->and($dto->statusMessage)->toBe('你的 Apple ID 或密码不正确。');
});

// 测试服务器错误
it('测试服务器错误响应', function () {
    $mockClient = new MockClient([
        AddFamilyMemberRequest::class => MockResponse::make(
            body: ['error' => 'Internal Server Error'],
            status: 500
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);

    expect(fn() => $this->icloudConnector->send($this->request))
        ->toThrow(RequestException::class);
});
