<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfoData;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\CreateFamilyRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\CreateFamily\CreateFamily;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyInfo\FamilyInfo;
uses(TestCase::class);


beforeEach(function () {

    $this->appleId  = 'testAppleId';
    $this->password = 'testPassword';

    $this->organizerAppleId                       = 'testAppleId';
    $this->organizerAppleIdForPurchasesPassword   = 'testPassword';
    $this->organizerAppleIdForPurchases           = 'testPassword';
    $this->organizerShareMyLocationEnabledDefault = true;
    $this->iTunesTosVersion                       = 284005;

    $this->request = new CreateFamilyRequest(
        CreateFamily::from([
            'organizerAppleId'                       => $this->organizerAppleId,
            'organizerAppleIdForPurchasesPassword'   => $this->organizerAppleIdForPurchasesPassword,
            'organizerAppleIdForPurchases'           => $this->organizerAppleIdForPurchases,
            'organizerShareMyLocationEnabledDefault' => $this->organizerShareMyLocationEnabledDefault,
            'iTunesTosVersion'                       => $this->iTunesTosVersion,
        ])

    );


    $this->account = new Account($this->appleId, $this->password);
    // 创建 IcloudConnector 实例
    $this->icloudConnector = new IcloudConnector(
        new Apple(account: $this->account, config: new \Modules\AppleClient\Service\Config\Config())
    );

});

it('test request', function () {

    expect(CreateFamilyRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();

});

it('test createDtoFromResponse service error', function () {

    $mockClient = new MockClient([
        CreateFamilyRequest::class => MockResponse::make(body: ['service error'], status: 500),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(InternalServerErrorException::class);

it('test createDtoFromResponse error', function () {

    $mockClient = new MockClient([
        CreateFamilyRequest::class => MockResponse::make(
            body: '{
    "status-message": "此账户已经由其他家庭成员共享。当每位家庭成员都使用自己的账户时，“家人共享”效果最佳。",
    "title": "“家庭”中已有此账户",
    "status": 55
}'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())
        ->toBeInstanceOf(FamilyInfo::class)
        ->and($response->dto()->statusMessage)
        ->toBe('此账户已经由其他家庭成员共享。当每位家庭成员都使用自己的账户时，“家人共享”效果最佳。');
});


it('test createDtoFromResponse success', function () {

    $mockClient = new MockClient([
        CreateFamilyRequest::class => MockResponse::make(
            body: file_get_contents(
            base_path('/Modules/AppleClient/tests/Unit/Files/createFamily.json')
        )
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())
        ->toBeInstanceOf(FamilyInfo::class)
        ->and($response->dto()->statusMessage)
        ->toBe('Member of a family.');
});
