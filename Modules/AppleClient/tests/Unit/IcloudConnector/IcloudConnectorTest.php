<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\LoginDelegatesRequest;
use Saloon\Http\Connector;

uses(TestCase::class);

beforeEach(function () {

    $this->appleId  = 'testAppleId';
    $this->password = 'testPassword';

    $this->IcloudConnector = new IcloudConnector(
        new AppleClient(new Account($this->appleId, $this->password))
    );

    $this->request = new LoginDelegatesRequest($this->appleId, $this->password);
});

it('test IcloudConnector', function () {

    expect(IcloudConnector::class)
        ->toBeSaloonConnector()
        ->toUseBasicAuthentication();
});



