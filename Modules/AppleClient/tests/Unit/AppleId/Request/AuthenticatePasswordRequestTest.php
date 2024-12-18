<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\AppleId\AppleIdConnector;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AuthenticatePasswordRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Exceptions\Request\Statuses\TooManyRequestsException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Carbon\Carbon;

uses(TestCase::class);

beforeEach(function () {
    // 设置测试密码
    $this->password = 'test-password-123';

    // 创建请求实例
    $this->request = new AuthenticatePasswordRequest($this->password);

    // 创建 Connector 实例，模拟真实的 AppleID 连接器
    $account         = new Account('test@example.com', $this->password);
    $apple           = new Apple(account: $account, config: new Config());
    $this->connector = new AppleIdConnector(
        $apple
    );
});

// 测试请求的基本属性
it('测试请求的基本属性', function () {
    expect(AuthenticatePasswordRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();
});

// 测试请求端点
it('测试请求端点正确', function () {
    expect($this->request->resolveEndpoint())
        ->toBe('/authenticate/password');
});

// 测试请求体内容
it('测试请求体内容', function () {
    $expectedBody = [
        'password' => $this->password,
    ];

    // 创建一个 mock 客户端来捕获请求
    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make([
            'success' => true,
        ]),
    ]);

    // ��送请求
    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

    // 验证发送的请求体
    $mockClient->assertSent(AuthenticatePasswordRequest::class, function ($request) use ($expectedBody) {
        return $request->body()->all() === $expectedBody;
    });
});

// 测试密码验证成功
it('测试密码验证成功场景', function () {
    // 模拟成功响应数据
    $mockResponse = [
        'hasValidatePassword' => true,
        'update_at'           => now()->toISOString(),
    ];

    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make($mockResponse),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($this->request);
    $dto      = $response->dto();

    // 验证响应数据的完整性
    expect($dto->hasValidatePassword)->toBeTrue()
        ->and($dto->updateAt)->toBeInstanceOf(Carbon::class);
});

// 测试密码错误
it('测试密码错误场景', function () {

    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make(
            status: 401
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(UnauthorizedException::class);

// 测试需要重新验证密码（409状态码）
it('测试需要重新验证密码场景', function () {
    $mockResponse = [
        'hasValidatePassword' => false,
        'update_at'           => now()->toISOString(),
    ];

    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make(
            body: $mockResponse,
            status: 409
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($this->request);
    $dto      = $response->dto();

    // 409 状态码是特殊情况，不应该抛出异常
    expect($dto->hasValidatePassword)->toBeFalse()
        ->and($dto->updateAt)->toBeInstanceOf(Carbon::class);
});

// 测试账号被锁定
it('测试账号被锁定场景', function () {
    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make(
            status: 403
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(ForbiddenException::class);

// 测试密码过期
it('测试密码过期场景', function () {

    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make(
            status: 401
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(UnauthorizedException::class);

// 测试请求频率限制
it('测试请求频率限制场景', function () {
    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make(
            status: 429
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(TooManyRequestsException ::class);

// 测试服务器错误
it('测试服务器错误场景', function () {

    $mockClient = new MockClient([
        AuthenticatePasswordRequest::class => MockResponse::make(
            status: 500
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(InternalServerErrorException ::class);
