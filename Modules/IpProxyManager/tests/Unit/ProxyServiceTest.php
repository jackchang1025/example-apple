<?php


use Illuminate\Support\Collection;
use Modules\IpProxyManager\Service\BaseDto;
use Modules\IpProxyManager\Service\Exception\ProxyException;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\ProxyService;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

beforeEach(function () {

    $this->proxyService = Mockery::mock(ProxyService::class)->makePartial();
});


test('it can be initialised', function () {

    $ProxyResponse = Mockery::mock(ProxyResponse::class);

    $this->proxyService->setProxy($ProxyResponse);

    expect($this->proxyService->getProxy())->toBe($ProxyResponse);

});

test('it can be initialised2', function () {

    $this->proxyService->shouldReceive('send')
        ->once()
        ->andThrow(Mockery::mock(FatalRequestException::class));

    expect($this->proxyService->getProxy())->toBeNull();

});


test('it can be initialised3', function () {


    $this->proxyService->shouldReceive('send')
        ->once()
        ->andThrow(Mockery::mock(RequestException::class));

    expect($this->proxyService->getProxy())->toBeNull();

});

test('it can be initialised4', function () {

    $response = Mockery::mock(\Saloon\Http\Response::class);
    $response->shouldReceive('dto')->andThrow(Mockery::mock(ProxyException::class));

    $this->proxyService->shouldReceive('send')
        ->once()
        ->andReturn($response);

    expect($this->proxyService->getProxy())->toBeNull();

});

test('it can be initialised5', function () {

    $response = Mockery::mock(\Saloon\Http\Response::class);
    $response->shouldReceive('dto')->andThrow(Mockery::mock(\JsonException::class));

    $this->proxyService->shouldReceive('send')
        ->once()
        ->andReturn($response);

    expect($this->proxyService->getProxy())->toBeNull();

});

test('it can be initialised6', function () {

    $ProxyResponse = Mockery::mock(ProxyResponse::class);

    $collection = Mockery::mock(Collection::class);
    $collection->shouldReceive('first')->andReturn($ProxyResponse);

    $dto = Mockery::mock(BaseDto::class);
    $dto->shouldReceive('getProxyList')->andReturn($collection);


    $response = Mockery::mock(\Saloon\Http\Response::class);
    $response->shouldReceive('dto')->andThrow($dto);

    $this->proxyService->shouldReceive('send')
        ->once()
        ->andReturn($response);

    expect($this->proxyService->getProxy())->toBe($ProxyResponse);

});

test('it can be initialised7', function () {

    $ProxyResponse = Mockery::mock(ProxyResponse::class);

    $this->proxyService->setProxy($ProxyResponse);

    $ProxyResponse1 = $this->proxyService->getProxy();

    $this->proxyService->shouldAllowMockingProtectedMethods();
    $this->proxyService->shouldReceive('fetchProxyFirst')
        ->once()
        ->andReturnUsing(function () {
            return Mockery::mock(ProxyResponse::class);
        });

    $this->proxyService->refreshProxy();

    $ProxyResponse2 = $this->proxyService->getProxy();

    expect($ProxyResponse1)->toBe($ProxyResponse)
        ->and($ProxyResponse1)
        ->not->toBe($ProxyResponse2);

});

