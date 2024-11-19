<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\LoginDelegates;
use Modules\AppleClient\Service\Exception\AppleRequestException\LoginRequestException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\LoginDelegatesRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);


beforeEach(function () {

    $this->appleId = 'testAppleId';
    $this->password = 'testPassword';
    $this->authCode = null;
    $this->request = new LoginDelegatesRequest($this->appleId, $this->password, $this->authCode);

    $this->icloudConnector = new IcloudConnector(
        new AppleClient(new Account($this->appleId, $this->password))
    );

});

it('test request', function () {

    expect(LoginDelegatesRequest::class)
        ->toBeSaloonRequest()
        ->toSendPostRequest()
        ->toUseBasicAuthentication()
        ->toHaveXmlBody();

});

it('test createDtoFromResponse CannotCreateData', function () {

    $mockClient = new MockClient([
        LoginDelegatesRequest::class => MockResponse::make(),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(
    ErrorException::class,
);


it('test createDtoFromResponse', function () {

    $mockClient = new MockClient([
        LoginDelegatesRequest::class => MockResponse::make(
            body: file_get_contents(
            base_path('/Modules/AppleClient/tests/Unit/Files/login.xml')
        )
        ),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class)->and($response->dto())->toBeInstanceOf(
        LoginDelegates::class
    );
});

it('test createDtoFromResponse service error', function () {

    $mockClient = new MockClient([
        LoginDelegatesRequest::class => MockResponse::make(body: ['service error'], status: 500),
    ]);

    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(InternalServerErrorException::class);

it('test createDtoFromResponse login error', function () {

    $mockClient = new MockClient([
        LoginDelegatesRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
    <dict>
        <key>status-message</key>
        <string>Your Apple ID or password was entered incorrectly.</string>
        <key>status</key>
        <integer>1</integer>
    </dict>
</plist>'
        ),
    ]);

    $this->request = new LoginDelegatesRequest($this->appleId, $this->password);
    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(LoginRequestException::class, 'Your Apple ID or password was entered incorrectly.');

it('test createDtoFromResponse login success', function () {

    $mockClient = new MockClient([
        LoginDelegatesRequest::class => MockResponse::make(
            body: file_get_contents(
            base_path('/Modules/AppleClient/tests/Unit/Files/auth.xml')
        )
        ),
    ]);

    $this->request = new LoginDelegatesRequest($this->appleId, $this->password);
    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())
        ->toBeInstanceOf(LoginDelegates::class);
});

it('test createDtoFromResponse auth error', function () {

    $mockClient = new MockClient([
        LoginDelegatesRequest::class => MockResponse::make(
            body: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
    <dict>
        <key>status-message</key>
        <string>We can not process your request, please try again later.</string>
        <key>status</key>
        <integer>1</integer>
    </dict>
</plist>'
        ),
    ]);

    $this->request = new LoginDelegatesRequest($this->appleId, $this->password, time());
    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
})->throws(VerificationCodeException::class, 'We can not process your request, please try again later.');

it('test createDtoFromResponse auth success', function () {

    $mockClient = new MockClient([
        LoginDelegatesRequest::class => MockResponse::make(
            body: file_get_contents(
            base_path('/Modules/AppleClient/tests/Unit/Files/login.xml')
        )
        ),
    ]);

    $this->request = new LoginDelegatesRequest($this->appleId, $this->password, time());
    $this->icloudConnector->withMockClient($mockClient);
    $response = $this->icloudConnector->send($this->request);

    expect($response)
        ->toBeInstanceOf(\Saloon\Http\Response::class)
        ->and($response->dto())
        ->toBeInstanceOf(LoginDelegates::class);
});
