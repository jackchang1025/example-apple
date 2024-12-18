<?php

use Modules\AppleClient\Service\Resources\Web\WebResource;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\AppleAuthenticationConnector;
use Modules\AppleClient\Service\Resources\Web\AppleId\AppleIdResource;
use Modules\AppleClient\Service\Resources\Web\Idmsa\IdmsaResource;
use Modules\AppleClient\Service\Resources\Web\Icloud\IcloudResource;
use Saloon\Repositories\ArrayStore;

uses(TestCase::class);


beforeEach(function () {
    /** @var Apple $apple */
    $this->apple = Mockery::mock(Apple::class)->makePartial();

    // 模拟构造函数中需要的方法
    $this->webResource = new WebResource($this->apple);
});

test('test get apple authentication connector', function () {

    $this->apple->shouldReceive('getConfig')
        ->once()
        ->andReturn(
            new \Modules\AppleClient\Service\Config\Config([
                'apple_auth' => [
                    'url' => 'https://appleid.apple.com',
                ],
            ])
        );

    expect($this->webResource->getAppleAuthenticationConnector())->toBeInstanceOf(AppleAuthenticationConnector::class);
});

it('test get apple id resource', function () {
    expect($this->webResource->getAppleIdResource())->toBeInstanceOf(AppleIdResource::class);
});


test('test web idmsa resource', function () {
    expect($this->webResource->getIdmsaResource())->toBeInstanceOf(IdmsaResource::class);
});

test('test web icloud resource', function () {
    expect($this->webResource->getIcloudResource())->toBeInstanceOf(IcloudResource::class);
});
