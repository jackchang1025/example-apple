<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\RemoveFamilyMemberRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyInfo\FamilyInfo;
uses(TestCase::class);


beforeEach(function () {

    $this->appleId = 'testAppleId';
    $this->password = 'testPassword';

    $this->dsid = 'testAppleId';

    $this->request = new RemoveFamilyMemberRequest($this->dsid);

    // 创建 IcloudConnector 实例
    $this->account = new Account($this->appleId, $this->password);
    // 创建 IcloudConnector 实例
    $this->icloudConnector = new IcloudConnector(
        new Apple(account: $this->account, config: new \Modules\AppleClient\Service\Config\Config())
    );

});

it('test request', function () {

    expect(RemoveFamilyMemberRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toHaveJsonBody();

});

it('test RemoveFamilyMemberRequest service error', function () {

    $mockClient = new MockClient([
        RemoveFamilyMemberRequest::class => MockResponse::make(body: ['service error'], status: 500),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(InternalServerErrorException::class);

it('test createDtoFromResponse success', function () {

    $mockClient = new MockClient([
        RemoveFamilyMemberRequest::class => MockResponse::make(
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
