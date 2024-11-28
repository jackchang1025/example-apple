<?php

use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\Integrations\AppleAuth\AppleAuthConnector;
use Modules\AppleClient\Service\Integrations\AppleId\AppleIdConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthorizeSignIn;
use Modules\IpProxyManager\Service\ProxyResponse;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;


beforeEach(function () {

    $this->appleClient = new Modules\AppleClient\Service\AppleClient();

    $this->proxy = Mockery::mock(ProxyService::class);

    $this->log = Mockery::mock(LoggerInterface::class);

//    $this->appleClient = Mockery::mock(\Modules\AppleClient\Service\AppleClient::class);

});

// 测试AppleClient的构造函数
test('AppleClient can be instantiated', function () {
    $client = new AppleClient();

    expect($client)->toBeInstanceOf(AppleClient::class)
        ->and($client->getAppleIdConnector())->toBeInstanceOf(AppleIdConnector::class)
        ->and($client->getIdmsaConnector())->toBeInstanceOf(IdmsaConnector::class)
        ->and($client->getAppleAuthConnector())->toBeInstanceOf(AppleAuthConnector::class);
});

// 测试序列化和反序列化
test('AppleClient can be serialized and unserialized', function () {

    $proxy  = Mockery::mock(ProxyService::class);
    $client = new AppleClient();
    $client->withConfig(['key' => 'value']);
    $client->withProxy($proxy);
    $client->withCookieJar(
        [
            [
                'Name'   => 'test_cookie',
                'Value'  => 'test_value',
                'Domain' => 'example.com',
            ],
        ]
    );
    $client->withHeaderRepositories(['User-Agent' => 'TestAgent']);


    expect($client)->toBeInstanceOf(AppleClient::class)
        ->and($client->config()->get('key'))->toBe('value')
        ->and($client->getProxy())->toBe($proxy)
        ->and($client->getCookieJar()?->getCookieByName('test_cookie')?->getValue())->toBe('test_value')
        ->and($client->getHeaderRepositories()?->get('User-Agent'))->toBe('TestAgent');
});

// 测试Getter方法
test('AppleClient getters return correct instances', function () {
    $client = new AppleClient();

    expect($client->getAppleIdConnector())->toBeInstanceOf(AppleIdConnector::class)
        ->and($client->getIdmsaConnector())->toBeInstanceOf(IdmsaConnector::class)
        ->and($client->getAppleAuthConnector())->toBeInstanceOf(AppleAuthConnector::class);
});

// 测试配置的设置和获取
test('AppleClient can set and get config', function () {
    $client = new AppleClient();
    $client->withConfig(['testKey' => 'testValue']);

    expect($client->config()->get('testKey'))->toBe('testValue');
});

// 测试代理的设置和获取
test('AppleClient can set and get proxy', function () {
    $client = new AppleClient();
    $client->withProxy();

    expect($client->getProxy())->toBeNull();
});

// 测试Cookie的设置和获取
test('AppleClient can set and get cookies', function () {
    $client = new AppleClient();
    $client->withCookieJar([
        [
            'Name'   => 'test_cookie',
            'Value'  => 'test_value',
            'Domain' => 'example.com',
        ],
    ]);

    expect($client->getCookieJar()?->getCookieByName('test_cookie')?->getValue())->toBe('test_value');
});

// 测试Header的设置和获取
test('AppleClient can set and get headers', function () {
    $client = new AppleClient();
    $client->withHeaderRepositories(['X-Test-Header' => 'TestValue']);

    expect($client->getHeaderRepositories()->get('X-Test-Header'))->toBe('TestValue');
});

it('can get a list of all the apple clients', function () {

    $ProxyResponse      = Mockery::mock(ProxyResponse::class);
    $ProxyResponse->url = 'http://127.0.0.1:'.random_int(100, 999);

    $this->proxy = Mockery::mock(ProxyService::class)->makePartial();
    $this->proxy->setProxy($ProxyResponse);

    $this->proxy->shouldReceive('isProxyEnabled')->times(9)->andReturnTrue();

    $this->proxy->shouldReceive('refreshProxy')->times(4)->andReturnUsing(function () {

        $ProxyResponse      = Mockery::mock(ProxyResponse::class);
        $ProxyResponse->url = 'http://127.0.0.1:'.random_int(100, 999);

        $this->proxy->setProxy($ProxyResponse);

        return $ProxyResponse;
    });

    $this->appleClient->withProxy($this->proxy);

    $this->appleClient->withLogger($this->log);

    $proxy = [];

    $this->log->shouldReceive('debug')->times(5)->andReturnUsing(function ($dd, $config) use (&$proxy) {

        $proxy[] = $config['config']['proxy'];

        return true;
    });

    $attempts = 1;
    $oldUrl   = [];
    $this->appleClient->withHandleRetry(
        function (FatalRequestException|RequestException $exception, Request $request) use (&$attempts, &$oldUrl) {

            $oldUrl[] = $this->proxy->getProxy()->url;

            $attempts++;

            if ($exception instanceof FatalRequestException && $this->proxy->isProxyEnabled()) {
                $this->proxy->refreshProxy();

                return true;
            }

            return true;
        }
    );

    try {

        $this->appleClient->sign();

    } catch (\Saloon\Exceptions\Request\FatalRequestException $e) {

    }

    dd($proxy);
    expect($attempts)->toBe(5)->and(array_unique($oldUrl))->toHaveCount(4)->and($proxy)->dump();
});

it('can get a list of all the proxy failed', function () {

    $this->proxy = Mockery::mock(ProxyService::class)->makePartial();

    $this->proxy->shouldReceive('isProxyEnabled')->times(1)->andReturnTrue();

    $this->proxy->shouldReceive('getProxy')->times(1)->andReturnNull();

    $this->appleClient->withProxy($this->proxy);

    $config = [];
    $this->appleClient->middleware()->onRequest(function (PendingRequest $pendingRequest) use (&$config) {

        $config = $pendingRequest->config()->all();

        return $pendingRequest;
    });

    MockClient::global()->addResponse(
        MockResponse::make(
            status: 200
        ),
        AuthorizeSignIn::class
    );

    $this->appleClient->sign();

    expect($config['proxy'])->toBeNull();
});

it('can get a list of all the proxy', function () {

    $ProxyResponse      = Mockery::mock(ProxyResponse::class);
    $ProxyResponse->url = 'http://127.0.0.1:8888';

    $this->proxy = Mockery::mock(ProxyService::class)->makePartial();

    $this->proxy->shouldReceive('isProxyEnabled')->times(1)->andReturnTrue();

    $this->proxy->shouldReceive('getProxy')->times(1)->andReturn($ProxyResponse);

    $this->appleClient->withProxy($this->proxy);

    $config = [];
    $this->appleClient->middleware()->onRequest(function (PendingRequest $pendingRequest) use (&$config) {

        $config = $pendingRequest->config()->all();

        return $pendingRequest;
    });

    MockClient::global()->addResponse(
        MockResponse::make(
            status: 200
        ),
        AuthorizeSignIn::class
    );

    $this->appleClient->sign();

    expect($config['proxy'])->toBe('http://127.0.0.1:8888');
});

it('can get a list of all the proxy2', function () {

    $ProxyResponse = Mockery::mock(ProxyResponse::class);

    $ProxyResponse->url = 'http://127.0.0.1:8888';

    $this->proxy = Mockery::mock(ProxyService::class)->makePartial();

    $this->proxy->shouldReceive('isProxyEnabled')->times(2)->andReturnTrue();

    $this->proxy->shouldReceive('getProxy')->times(2)->andReturn($ProxyResponse);

    $this->appleClient->withProxy($this->proxy);

    MockClient::global()->addResponse(
        MockResponse::make(
            status: 200
        ),
        AuthorizeSignIn::class
    );


    MockClient::global()->addResponse(
        MockResponse::make(
            status: 200
        ),
        \Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\Auth::class
    );

    $config = [];
    $this->appleClient->middleware()->onRequest(function (PendingRequest $pendingRequest) use (&$config) {

        $config = $pendingRequest->config()->all();

        return $pendingRequest;
    });

    $this->appleClient->sign();

    $this->appleClient->auth();

});







































