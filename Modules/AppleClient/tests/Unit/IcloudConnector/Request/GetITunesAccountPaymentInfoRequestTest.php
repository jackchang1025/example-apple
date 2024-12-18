<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\GetITunesAccountPaymentInfoRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;
uses(TestCase::class);

beforeEach(function () {
    // 设置基础测试数据
    $this->appleId  = 'testAppleId';
    $this->password = 'testPassword';

    // 设置请求参数
    $this->organizerDSID = '12345678';
    $this->userAction    = 'ADDING_FAMILY_MEMBER';
    $this->sendSMS       = true;

    // 创建请求实例
    $this->request = new GetITunesAccountPaymentInfoRequest(
        $this->organizerDSID,
        $this->userAction,
        $this->sendSMS
    );

    $this->account = new Account($this->appleId, $this->password);
    // 创建 IcloudConnector 实例
    $this->icloudConnector = new IcloudConnector(
        new Apple(account: $this->account, config: new \Modules\AppleClient\Service\Config\Config())
    );
});

// 测试请求基本属性
it('测试请求的基本属性', function () {
    expect(GetITunesAccountPaymentInfoRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();
});

// 测试请求体内容
it('测试请求体包含正确的参数', function () {
    $expectedBody = [
        'organizerDSID' => $this->organizerDSID,
        'userAction'    => $this->userAction,
        'sendSMS'       => $this->sendSMS,
    ];

    expect($this->request->defaultBody())->toBe($expectedBody);
});

// 测试服务端错误响应
it('测试服务器错误响应处理', function () {
    $mockClient = new MockClient([
        GetITunesAccountPaymentInfoRequest::class => MockResponse::make(
            body: ['service error'],
            status: 500
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(InternalServerErrorException::class);

// 测试业务错误响应
it('测试业务错误响应处理', function () {
    $mockClient = new MockClient([
        GetITunesAccountPaymentInfoRequest::class => MockResponse::make(
            body: json_encode([
                "status"         => 1,
                "status-message" => "无效的 DSID",
            ], JSON_THROW_ON_ERROR)
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())
        ->toBeInstanceOf(ITunesAccountPaymentInfo::class)
        ->and($response->dto()->statusMessage)
        ->toBe('无效的 DSID');
});

// 测试成功响应
it('测试成功响应处理', function () {
    $mockClient = new MockClient([
        GetITunesAccountPaymentInfoRequest::class => MockResponse::make(
            body: '{
    "userAction": "ADDING_FAMILY_MEMBER",
    "status-message": "Success",
    "billingType": "Card",
    "creditCardImageUrl": "https://setup.icloud.com/resource/a3780ba63cbe/imgs/family/MasterCard@2x.png",
    "creditCardLastFourDigits": "3030",
    "verificationType": "CVV",
    "creditCardId": "MAST",
    "creditCardType": "MasterCard",
    "status": 0
}'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())
        ->toBeInstanceOf(ITunesAccountPaymentInfo::class)
        ->and($response->dto()->statusMessage)
        ->toBe('Success')
        ->and($response->dto()->status)
        ->toBe(0)
        ->and($response->dto()->billingType)
        ->toBe('Card')
        ->and($response->dto()->creditCardLastFourDigits)
        ->toBe('3030');
});

// 测试请求端点
it('测试请求端点正确', function () {
    expect($this->request->resolveEndpoint())
        ->toBe('/setup/mac/family/getiTunesAccountPaymentInfo');
});

// 测试自定义参数场景
it('测试使用自定义参数创建请求', function () {
    $customRequest = new GetITunesAccountPaymentInfoRequest(
        organizerDSID: '87654321',
        userAction: 'CUSTOM_ACTION',
        sendSMS: false
    );

    expect($customRequest->defaultBody())
        ->toBe([
            'organizerDSID' => '87654321',
            'userAction'    => 'CUSTOM_ACTION',
            'sendSMS'       => false,
        ]);
});
