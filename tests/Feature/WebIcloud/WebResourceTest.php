<?php

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\AuthRequest;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SendPhoneSecurityCodeRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\VerifyPhoneSecurityCodeRequest;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\Devices\Devices;
use Modules\AppleClient\Service\Integrations\WebIcloud\Request\AccountLoginRequest;
use Modules\AppleClient\Service\Integrations\WebIcloud\Request\GetDevicesRequest;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\SimpleCache\CacheInterface;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInInitRequest as AppleAuthenticationConnectorSignInInitRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SignInCompleteRequest as IdmsaSignInCompleteRequest;
use Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth\SignInInitRequest as IdmsaSignInInitRequest;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request\SignInCompleteRequest as AppleAuthenticationConnectorSignInCompleteRequest;
use \Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\Auth\Auth;

uses(TestCase::class);


uses()
    ->beforeEach(fn() => MockClient::destroyGlobal())
    ->in(__DIR__);


beforeEach(function () {

    $this->appleId  = 'testAppleId';
    $this->password = 'testPassword';

    $this->cache      = app(CacheInterface::class);
    $this->dispatcher = app(Dispatcher::class);

    $this->account = Account::from([
        'account'  => $this->appleId,
        'password' => $this->password,
    ]);

    $this->builder = new AppleBuilder(
        cache: $this->cache,
        dispatcher: $this->dispatcher
    );

    $this->builder->config()->add('debug', true);

    $this->mockClient = new MockClient([]);

    // Mock ProxyService
    $proxyService = Mockery::mock(ProxyService::class);
    $proxyService->shouldReceive('isProxyEnabled')->andReturn(false);
    $proxyService->shouldReceive('refreshProxy')->andReturn(null);
    app()->instance(ProxyService::class, $proxyService);

});

//测试登录抛出异常
it('login throw exception', function () {

    /** @var Apple $this ->apple */
    $this->apple = $this->builder->build($this->account);

    $this->apple->getWebResource()->getIcloudResource()->signIn();

})->throws(RequestException::class);

//测试登录成功
it('login success', function () {

    $this->mockClient = new MockClient([
        AppleAuthenticationConnectorSignInInitRequest::class     => MockResponse::fixture('sign/auth/init'),
        IdmsaSignInInitRequest::class                            => MockResponse::fixture('sign/idmsa/init'),
        AppleAuthenticationConnectorSignInCompleteRequest::class => MockResponse::fixture('sign/auth/complete'),
        IdmsaSignInCompleteRequest::class                        => MockResponse::fixture('sign/idmsa/complete'),
        AuthRequest::class                                       => MockResponse::fixture('sign/idmsa/auth'),
    ]);

    $this->apple = $this->builder->build(Account::from([
        'account'  => 'jackchang2021@163.com',
        'password' => 'AtA3FH2sBfrtSv6',
    ]))->withMockClient($this->mockClient);//

    $response = $this->apple->getWebResource()->getIdmsaResource()->signIn();

    //获取 授权信息
    $authInfo = $this->apple->getWebResource()->getIdmsaResource()->getAuth();

    expect($response)->toBeInstanceOf(SignInComplete::class)->and($authInfo)->toBeInstanceOf(Auth::class);
});

it('icloud login ', function () {

    $this->apple = $this->builder->build(Account::from([
        'account'          => '674648134@qq.com',
        'password'         => 'AtA3FH2sBfrtSv6',
        'bindPhone'        => '+85293210810',
        'bindPhoneAddress' => 'http://gsm888.vip/api/sms/recordText?key=a0cefca07f1a42f9af342ead359fc132',
    ]));

    $this->mockClient = new MockClient([

        AppleAuthenticationConnectorSignInInitRequest::class     => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/auth/init"
        ),
        IdmsaSignInInitRequest::class                            => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/idmsa/init"
        ),
        AppleAuthenticationConnectorSignInCompleteRequest::class => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/auth/complete"
        ),
        IdmsaSignInCompleteRequest::class                        => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/idmsa/complete"
        ),
        AuthRequest::class                                       => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/idmsa/auth"
        ),
        AccountLoginRequest::class                               => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/idmsa/accountLogin"
        ),
        SendPhoneSecurityCodeRequest::class                      => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/idmsa/SendPhoneSecurityCodeRequest"
        ),
        VerifyPhoneSecurityCodeRequest::class                    => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/idmsa/VerifyPhoneSecurityCodeRequest"
        ),
        GetDevicesRequest::class                                 => MockResponse::fixture(
            "{$this->apple->getAccount()->getAccount()}/icloud/sign/idmsa/GetDevicesRequest"
        ),
    ]);

    $this->apple->withMockClient($this->mockClient);

    $response = $this->apple->getWebResource()->getIcloudResource()->getAuthenticateResources()->autoAuth();

    $devices = $this->apple->getWebResource()->getIcloudResource()->getDevices();

    expect($response)->toBeInstanceOf(
        \Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\AccountLogin\AccountLogin::class
    )->and($devices)->toBeInstanceOf(Devices::class)->dump();
});
