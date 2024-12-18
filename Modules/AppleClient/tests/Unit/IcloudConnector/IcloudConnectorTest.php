<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\LoginDelegatesRequest;

uses(TestCase::class);

beforeEach(function () {

    $this->appleId  = 'testAppleId';
    $this->password = 'testPassword';

    $this->IcloudConnector = new IcloudConnector(
        new Apple(
            new Account($this->appleId, $this->password),
            Mockery::mock(\Modules\AppleClient\Service\Config\Config::class)
        )
    );

    $this->request = new LoginDelegatesRequest($this->appleId, $this->password);
});

it('test IcloudConnector', function () {

    expect(IcloudConnector::class)
        ->toBeSaloonConnector()
        ->toUseBasicAuthentication();
});



