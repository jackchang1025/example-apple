<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\AppleAuthenticationConnector;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Request\SignInComplete;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInCompleteRequest;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;


uses(TestCase::class);

beforeEach(function () {
    // 设置测试数据
    $this->signInCompleteData = SignInComplete::from([
        'key'       => 'test-key-123',
        'b'         => 'test-b',
        'c'         => 'test-c',
        'salt'      => 'test-salt',
        'password'  => 'hashed-password',
        'iteration' => '1000',
        'protocol'  => 'test-protocol',
    ]);

    // 创建请求实例
    $this->request = new SignInCompleteRequest($this->signInCompleteData);

    // 创建 Connector 实例
    $account         = new Account('test@example.com', 'password');
    $apple           = new Apple(account: $account, config: new Config());
    $this->connector = new AppleAuthenticationConnector(
        $apple,
        'https://test.example.com' // 添加基础 URL 参数
    );
});

// 测试请求的基本属性
it('测试请求的基本属性', function () {
    expect(SignInCompleteRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();
});

// 测试请求端点
it('测试请求端点正确', function () {
    expect($this->request->resolveEndpoint())
        ->toBe('/complete');
});

// 测试默认请求体
it('测试默认请求体内容', function () {
    $expectedBody = [
        'key'   => 'test-key-123',
        'value' => [
            'b'         => 'test-b',
            'c'         => 'test-c',
            'salt'      => 'test-salt',
            'password'  => 'hashed-password',
            'iteration' => '1000',
            'protocol'  => 'test-protocol',
        ],
    ];

    expect($this->request->defaultBody())->toBe($expectedBody);
});

// 测试登录成功
it('测试登录成功场景', function () {
    $mockResponse = [
        'M1' => 'test-m1-value',
        'M2' => 'test-m2-value',
        'c'  => 'test-c-value',
    ];

    $mockClient = new MockClient([
        SignInCompleteRequest::class => MockResponse::make($mockResponse),
    ]);

    $this->connector->withMockClient($mockClient);
    $response = $this->connector->send($this->request);
    $dto      = $response->dto();

    expect($dto->M1)->toBe('test-m1-value')
        ->and($dto->M2)->toBe('test-m2-value')
        ->and($dto->c)->toBe('test-c-value');
});

// 测试密码错误
it('测试密码错误场景', function () {
    $mockResponse = [
        'error' => [
            'code'    => 'invalid_password',
            'message' => '密码错误，请重试',
        ],
    ];

    $mockClient = new MockClient([
        SignInCompleteRequest::class => MockResponse::make(
            body: $mockResponse,
            status: 400
        ),
    ]);

    $this->connector->withMockClient($mockClient);
    $this->connector->send($this->request);

})->throws(ClientException::class);


// 测试账号锁定
it('测试账号锁定场景', function () {
    $mockResponse = [
        'error' => [
            'code'    => 'account_locked',
            'message' => '由于多次错误尝试，账号已被临时锁定',
        ],
    ];

    $mockClient = new MockClient([
        SignInCompleteRequest::class => MockResponse::make(
            body: $mockResponse,
            status: 403
        ),
    ]);

    $this->connector->withMockClient($mockClient);

    $this->connector->send($this->request);

})->throws(ForbiddenException::class);
