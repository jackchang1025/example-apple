<?php

use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Delegate;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\LoginDelegates;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\MobileMe\MobileMe;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\MobileMe\ServiceData;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\MobileMe\Token;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Trait\HasLoginDelegates;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Http\Auth\BasicAuthenticator;

uses(\Illuminate\Foundation\Testing\TestCase::class);

class HasLoginDelegatesTest
{
    use HasLoginDelegates;

    private Account $account;
    private IcloudConnector $icloudConnector;

    public function __construct(Account $account, IcloudConnector $icloudConnector)
    {
        $this->account         = $account;
        $this->icloudConnector = $icloudConnector;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getIcloudConnector(): IcloudConnector
    {
        return $this->icloudConnector;
    }
}


beforeEach(function () {

    $this->appleId         = 'testAppleId';
    $this->password        = 'testPassword';
    $this->account         = new Account($this->appleId, $this->password);
    $this->icloudConnector = Mockery::mock(IcloudConnector::class);
    $this->testInstance    = new HasLoginDelegatesTest($this->account, $this->icloudConnector);

});

// 测试 getLoginDelegates 方法
test('test get login delegates', function () {

    expect($this->testInstance->getLoginDelegates())->toBeNull();
});

// 测试 loginDelegates 方法
test('test loginDelegates', function () {

    $mockResponse = Mockery::mock(Response::class);

    $testInstance = Mockery::mock(HasLoginDelegatesTest::class)->makePartial();
    $testInstance->shouldReceive('getIcloudConnector->getResources->loginDelegatesRequest')
        ->once()
        ->andReturn($mockResponse);

    $testInstance->shouldReceive('getAccount')
        ->times(2)
        ->andReturn($this->account);

    $result = $testInstance->loginDelegates();

    expect($result)
        ->toBe($mockResponse)
        ->and($testInstance->getLoginDelegates())
        ->toBeNull();
});

test('test authDelegates and return LoginDelegates', function () {


    $mockResources       = Mockery::mock(Resources::class);
    $mockIcloudConnector = Mockery::mock(IcloudConnector::class);
    $mockResponse        = Mockery::mock(Response::class);

    $mockLoginDelegates = Mockery::mock(LoginDelegates::class);
    $delegates          = Mockery::mock(Delegate::class);
    $mobileMeService    = Mockery::mock(MobileMe::class);
    $serviceData        = Mockery::mock(ServiceData::class);
    $tokens             = Mockery::mock(Token::class);


    $mockLoginDelegates->dsid      = '12345';
    $mockLoginDelegates->delegates = $delegates;

    $delegates->mobileMeService = $mobileMeService;

    $tokens->mmeAuthToken = 'token123';
    $serviceData->tokens  = $tokens;

    $mobileMeService->serviceData = $serviceData;


    // 设置预期行为
    $mockIcloudConnector->shouldReceive('getResources')
        ->once()
        ->andReturn($mockResources);

    $mockResources->shouldReceive('loginDelegatesRequest')
        ->once()
        ->andReturn($mockResponse);

    $mockResponse->shouldReceive('dto')
        ->once(3)
        ->andReturn($mockLoginDelegates);

    $mockIcloudConnector->shouldReceive('authenticate')
        ->once()
        ->withArgs(function ($authenticator) {
            return $authenticator instanceof BasicAuthenticator;
        });

    // 创建测试实例并执行
    $testInstance = new HasLoginDelegatesTest($this->account, $mockIcloudConnector);
    $result       = $testInstance->authDelegates('auth_code_123');

    expect($result)
        ->toBe($mockLoginDelegates)
        ->and($testInstance->getLoginDelegates())
        ->toBe($mockLoginDelegates);
});
//
// 测试 authDelegates 方法失败场景
test('test auth delegates', function () {

    $mockResponse = Mockery::mock(Response::class);

    $testInstance = Mockery::mock(HasLoginDelegatesTest::class)->makePartial();
    $testInstance->shouldReceive('getIcloudConnector->getResources->loginDelegatesRequest')
        ->once()
        ->andThrow(new VerificationCodeException(response: $mockResponse, message: 'Authentication failed'));

    $testInstance->shouldReceive('getAccount')
        ->times(2)
        ->andReturn($this->account);

    $testInstance->authDelegates('invalid_auth_code');

})->throws(VerificationCodeException::class, 'Authentication failed');
//
// 测试 getFamilyDetails 方法
test('test get family details', function () {

    $mockFamilyDetails = Mockery::mock(FamilyDetails::class);
    $mockResponse      = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('dto')
        ->once()
        ->andReturn($mockFamilyDetails);

    $testInstance = Mockery::mock(HasLoginDelegatesTest::class)->makePartial();
    $testInstance->shouldReceive('getIcloudConnector->getResources->getFamilyDetailsRequest')
        ->once()
        ->andReturn($mockResponse);

    $result = $testInstance->getFamilyDetails();

    expect($result)->toBe($mockFamilyDetails);
});


