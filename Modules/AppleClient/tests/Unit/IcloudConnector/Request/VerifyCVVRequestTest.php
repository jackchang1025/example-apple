<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\VerifyCVV\VerifyCVV;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\VerifyCVVRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\VerifyCVV\VerifyCVV as VerifyCVVResponse;
uses(TestCase::class);

beforeEach(function () {
    // 设置基础测试数据
    $this->appleId  = 'testAppleId';
    $this->password = 'testPassword';

    // 设置 CVV 验证请求的参数
    $this->creditCardLastFourDigits = '1234';
    $this->securityCode             = '123';
    $this->creditCardId             = 'MAST';
    $this->verificationType         = 'CVV';
    $this->billingType              = 'Card';

    $this->dto = \Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\VerifyCVV\VerifyCVV::from([
        'creditCardLastFourDigits' => $this->creditCardLastFourDigits,
        'securityCode'             => $this->securityCode,
        'creditCardId'             => $this->creditCardId,
        'verificationType'         => $this->verificationType,
        'billingType'              => $this->billingType,
    ]);
    // 创建请求实例
    $this->request = new VerifyCVVRequest(
        $this->dto
    );

    // 创建 IcloudConnector 实例
    $this->account = new Account($this->appleId, $this->password);
    // 创建 IcloudConnector 实例
    $this->icloudConnector = new IcloudConnector(
        new Apple(account: $this->account, config: new \Modules\AppleClient\Service\Config\Config())
    );
});

// 测试请求基本属性
it('测试 VerifyCVVRequest 基本属性', function () {
    expect(VerifyCVVRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();
});

// 测试请求端点
it('测试请求端点正确', function () {
    expect($this->request->resolveEndpoint())
        ->toBe('/setup/mac/family/verifyCVV');
});

// 测试默认请求体
it('测试默认请求体内容', function () {

    expect($this->request->defaultBody())->toBe($this->dto->toArray());
});

// 测试服务器错误响应
it('测试服务器错误响应处理', function () {
    $mockClient = new MockClient([
        VerifyCVVRequest::class => MockResponse::make(
            body: ['service error'],
            status: 500
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(InternalServerErrorException::class);

// 测试 CVV 验证成功
it('测试 CVV 验证成功场景', function () {
    $mockClient = new MockClient([
        VerifyCVVRequest::class => MockResponse::make(
            body: '{
                "status": 200,
                "status-message": "CVV verification successful",
                "verificationToken": "abc123xyz789",
                "code": 0
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($dto)
        ->toBeInstanceOf(VerifyCVVResponse::class)
        ->and($dto->statusMessage)
        ->toBe('CVV verification successful')
        ->and($dto->verificationToken)
        ->toBe('abc123xyz789')
        ->and($dto->status)
        ->toBe(200);
});

// 测试 CVV 验证失败 - 错误的 CVV
it('测试错误的 CVV 验证场景', function () {
    $mockClient = new MockClient([
        VerifyCVVRequest::class => MockResponse::make(
            body: '{
                "status": 1,
                "status-message": "Invalid security code",
                "code": 1
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($dto->status)->toBe(1)
        ->and($dto->statusMessage)->toBe('Invalid security code');
});

// 测试卡号后四位错误
it('测试卡号后四位错误场景', function () {
    $mockClient = new MockClient([
        VerifyCVVRequest::class => MockResponse::make(
            body: '{
                "status": 1,
                "status-message": "Card number does not match",
                "title": "安全码无效"
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);
    $dto      = $response->dto();

    expect($dto->status)->toBe(1)
        ->and($dto->statusMessage)->toBe('Card number does not match')->and($dto->title)->toBe('安全码无效');
});

// 测试无效的卡类型
it('测试无效的卡类型场景', function () {
    $invalidRequest = new VerifyCVVRequest(
        \Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\VerifyCVV\VerifyCVV::from([
            'creditCardLastFourDigits' => '1234',
            'securityCode'             => '123',
            'creditCardId'             => 'INVALID',
            'verificationType'         => '',
        ])
    );

    $mockClient = new MockClient([
        VerifyCVVRequest::class => MockResponse::make(
            body: '{
                "status": 1,
                "status-message": "Invalid card type"
            }'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($invalidRequest);
    $dto      = $response->dto();

    expect($dto->status)->toBe(1)
        ->and($dto->statusMessage)->toBe('Invalid card type');
});
