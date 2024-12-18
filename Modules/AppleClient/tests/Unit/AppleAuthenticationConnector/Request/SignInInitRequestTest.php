<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\AppleAuthenticationConnector;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInInitRequest;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\Statuses\NotFoundException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);

beforeEach(function () {
    // 设置测试账号
    $this->account = 'test@example.com';

    // 创建请求实例
    $this->request = new SignInInitRequest($this->account);

    // 创建 Connector 实例
    $account         = new Account($this->account, 'password');
    $apple           = new Apple(account: $account, config: new Config());
    $this->connector = new AppleAuthenticationConnector(
        $apple,
        'https://test.example.com'
    );
});

// 测试请求的基本属性
it('测试请求的基本属性', function () {
    expect(SignInInitRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();
});

// 测试请求端点
it('测试请求端点正确', function () {
    expect($this->request->resolveEndpoint())
        ->toBe('/init');
});

// 测试默认请求头
it('测试默认请求头内容', function () {
    $expectedHeaders = [
        'Accept'       => 'text/html',
        'Content-Type' => 'application/json',
    ];

    expect($this->request->defaultHeaders())->toBe($expectedHeaders);
});

// 测试默认请求体
it('测试默认请求体内容', function () {
    $expectedBody = [
        'email' => $this->account,
    ];

    expect($this->request->defaultBody())->toBe($expectedBody);
});

// 测试成功初始化登录
it('测试成功初始化登录场景', function () {
    $mockResponse = [
        'key'   => 'test-key-123',
        'value' => 'test-key-value',
    ];

    $mockClient = new MockClient([
        SignInInitRequest::class => MockResponse::make($mockResponse),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($this->request);
    $dto      = $response->dto();

    expect($dto->key)->toBe('test-key-123')
        ->and($dto->value)->toBe('test-key-value');
});

// 测试无效的邮箱格式
it('测试无效的邮箱格式场景', function () {
    $mockResponse = [
        'error' => [
            'code'    => 'invalid_email',
            'message' => '无效的邮箱格式',
        ],
    ];

    $mockClient = new MockClient([
        SignInInitRequest::class => MockResponse::make(
            body: $mockResponse,
            status: 400
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(ClientException::class);

// 测试账号不存在
it('测试账号不存在场景', function () {
    $mockResponse = [
        'error' => [
            'code'    => 'account_not_found',
            'message' => '该 Apple ID 不存在',
        ],
    ];

    $mockClient = new MockClient([
        SignInInitRequest::class => MockResponse::make(
            body: $mockResponse,
            status: 404
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(NotFoundException ::class);
