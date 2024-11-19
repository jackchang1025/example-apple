<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\RemoveFamilyMemberRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);


beforeEach(function () {

    $this->appleId = 'testAppleId';
    $this->password = 'testPassword';

    $this->dsid = 'testAppleId';

    $this->request = new RemoveFamilyMemberRequest($this->dsid);

    $this->icloudConnector = new IcloudConnector(
        new AppleClient(new Account($this->appleId, $this->password))
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
