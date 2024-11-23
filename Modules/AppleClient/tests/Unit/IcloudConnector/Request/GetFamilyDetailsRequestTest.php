<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\GetFamilyDetailsRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Spatie\LaravelData\DataCollection;

uses(TestCase::class);


beforeEach(function () {

    $this->appleId = 'testAppleId';
    $this->password = 'testPassword';
    $this->request = new GetFamilyDetailsRequest();

    $this->icloudConnector = new IcloudConnector(
        new AppleClient(new Account($this->appleId, $this->password))
    );

});

it('test request', function () {

    expect(GetFamilyDetailsRequest::class)
        ->toBeSaloonRequest()
        ->toSendGetRequest();

});

it('test createDtoFromResponse CannotCreateData', function () {

    $mockClient = new MockClient([
        GetFamilyDetailsRequest::class => MockResponse::make(),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    $response->dto();
})->throws(
    ErrorException::class,
);

it('test createDtoFromResponse error', function () {

    $mockClient = new MockClient([
        GetFamilyDetailsRequest::class => MockResponse::make(status: 500),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $this->icloudConnector->send($this->request);

})->throws(
    InternalServerErrorException::class,
);

it('test createDtoFromResponse Unauthorized', function () {

    $mockClient = new MockClient([
        GetFamilyDetailsRequest::class => MockResponse::make(status: 401),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $this->icloudConnector->send($this->request);

})->throws(
    UnauthorizedException::class,
);


it('test createDtoFromResponse', function () {

    $mockClient = new MockClient([
        GetFamilyDetailsRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
    <dict>
        <key>status-message</key>
        <string>Success</string>
        <key>dsid</key>
        <integer>21905965912</integer>
        <key>is-member-of-family</key>
        <false/>
        <key>status</key>
        <integer>0</integer>
    </dict>
</plist>'
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())->toBeInstanceOf(FamilyDetails::class)
        ->and($response->dto()->status)
        ->toBe(0)
        ->and($response->dto()->isMemberOfFamily)
        ->toBeFalse();
});

it('test success', function () {

    $mockClient = new MockClient([
        GetFamilyDetailsRequest::class => MockResponse::make(
            body: file_get_contents(
            base_path('/Modules/AppleClient/tests/Unit/Files/getFamilyDetails.xml')
        )
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())
        ->toBeInstanceOf(FamilyDetails::class)
        ->and($response->dto()->status)
        ->toBe(0)
        ->and($response->dto()->pendingMembers)
        ->toBeInstanceOf(DataCollection::class)
        ->and($response->dto()->isMemberOfFamily)
        ->toBeTrue();
});
