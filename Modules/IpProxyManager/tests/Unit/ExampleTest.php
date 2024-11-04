<?php

use Illuminate\Support\Collection;
use Modules\IpProxyManager\Service\Exception\ProxyException;
use Modules\IpProxyManager\Service\HuaSheng\Dto\ExtractDto;
use Modules\IpProxyManager\Service\HuaSheng\HuaShengConnector;
use Modules\IpProxyManager\Service\HuaSheng\Requests\ExtractRequest;
use Modules\IpProxyManager\Service\Stormproxies\DTO\AccountPasswordDto;
use Modules\IpProxyManager\Service\Stormproxies\DTO\DynamicDto;
use Modules\IpProxyManager\Service\Stormproxies\Request\AccountPasswordRequest;
use Modules\IpProxyManager\Service\Stormproxies\Request\DynamicRequest;
use Modules\IpProxyManager\Service\Stormproxies\StormConnector;
use Psr\Log\LoggerInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;


uses()
    ->beforeEach(fn() => MockClient::destroyGlobal())
    ->in(__DIR__);


test('Stormproxies AccountPasswordRequest example', function () {

    //构建一个花生代理连接器
    $connector = new StormConnector();

    // 创建一个模拟的 LoggerInterface
    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('debug')->andReturn(null);
    $mockLogger->shouldReceive('info')->andReturn(null);

    //设置日志
    $connector->withLogger($mockLogger);

    //账号密码模式
    $dto = new AccountPasswordDto([
        'username' => 'your_username',
        'password' => 'your_password',
        'host'     => 'proxy_host',
    ]);

    //构建请求
    $request  = new AccountPasswordRequest($dto);
    $response = $connector->send($request);


    /**
     * @var AccountPasswordDto $ipInfo
     */
    $ipInfo = $response->dto();

    expect($ipInfo)
        ->toBeInstanceOf(AccountPasswordDto::class)
        ->and($dto->getProxyList())
        ->toBeInstanceOf(Collection::class);

});


test('Stormproxies DynamicRequest InvalidArgument Exception example', function () {

    //构建一个花生代理连接器
    $connector = new StormConnector();

    // 创建一个模拟的 LoggerInterface
    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('debug')->andReturn(null);
    $mockLogger->shouldReceive('info')->andReturn(null);

    //设置日志
    $connector->withLogger($mockLogger);

    //账号密码模式
    $dto = new DynamicDto([
        'username' => 'your_username',
        'password' => 'your_password',
        'host'     => 'proxy_host',
    ]);

    //构建请求
    $request  = new DynamicRequest($dto);
    $response = $connector->send($request);

})->throws(InvalidArgumentException::class, '请配置代理 key');


test('Stormproxies DynamicRequest ProxyException example', function () {

    //构建一个花生代理连接器
    $connector = new StormConnector();

    // 创建一个模拟的 LoggerInterface
    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('debug')->andReturn(null);
    $mockLogger->shouldReceive('info')->andReturn(null);

    //设置日志
    $connector->withLogger($mockLogger);

    //账号密码模式
    $dto = new DynamicDto([
        'app_key'  => 'your_key',
        'password' => 'your_password',
        'host'     => 'proxy_host',
    ]);


    $mockClient = new MockClient([
        DynamicRequest::class => MockResponse::make([], 200),
    ]);

    $connector->withMockClient($mockClient);

    //构建请求
    $request  = new DynamicRequest($dto);
    $response = $connector->send($request);


    /**
     * @var DynamicDto $ipInfo
     */
    $ipInfo = $response->dto();

    expect($ipInfo)
        ->toBeInstanceOf(DynamicDto::class)
        ->and($dto->getProxyList())
        ->toBeInstanceOf(Collection::class);

})->throws(ProxyException::class);


test('HuaShengConnector Extract InvalidArgumentException  example', function () {

    //构建一个花生代理连接器
    $connector = new HuaShengConnector();

    //账号密码模式
    $dto = new ExtractDto([
        'app_key'  => 'your_key',
        'password' => 'your_password',
        'host'     => 'proxy_host',
    ]);

    //构建请求
    $request  = new ExtractRequest($dto);
    $response = $connector->send($request);

})->throws(InvalidArgumentException::class);


test('HuaShengConnector Extract ProxyException example', function () {

    //构建一个花生代理连接器
    $connector = new HuaShengConnector();

    //账号密码模式
    $dto = new ExtractDto([
        'session'  => 'your_key',
        'password' => 'your_password',
        'host'     => 'proxy_host',
    ]);

    //构建请求
    $request  = new ExtractRequest($dto);
    $response = $connector->send($request);

    /**
     * @var DynamicDto $ipInfo
     */
    $ipInfo = $response->dto();

})->throws(ProxyException::class);

