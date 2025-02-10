<?php

use App\Models\Phone;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Redis;
use Modules\AppleClient\Events\AccountBindPhoneFailEvent;
use Modules\AppleClient\Events\AccountBindPhoneSuccessEvent;
use Modules\AppleClient\Service\AddSecurityVerifyPhoneService;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumber;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumberVerification;
use Modules\PhoneCode\Service\PhoneCodeService;
use Psr\Log\LoggerInterface;
use Saloon\Http\Response;

uses(Tests\TestCase::class);

// 首先创建一个测试用的服务类，继承自原始服务类
class TestAddSecurityVerifyPhoneService extends AddSecurityVerifyPhoneService
{
    // 覆盖初始化方法，避免调用 trait 方法
    protected function initializeRetrySettings(): void
    {
        // 空实现，避免调用 trait 方法
    }
}

beforeEach(function () {
    // 创建 mock 对象
    $this->apple            = Mockery::mock(Apple::class);
    $this->phoneCodeService = Mockery::mock(PhoneCodeService::class);
    $this->dispatcher       = Mockery::mock(Dispatcher::class);
    $this->logger           = Mockery::mock(LoggerInterface::class);

    // 使用测试服务类替代原始服务类
    $this->service = new TestAddSecurityVerifyPhoneService(
        $this->apple,
        $this->phoneCodeService,
        $this->dispatcher,
        $this->logger
    );
});

// 测试正常流程
test('正常绑定流程', function () {


    $this->service->withTries(3)->withRetryInterval(1000)->withUseExponentialBackoff();
    // 新增 Account 模拟
    $mockAccount = Mockery::mock(\Modules\AppleClient\Service\DataConstruct\Account::class);
    $mockAccount->shouldReceive('getAttribute')->with('account')->andReturn('test@example.com');

    // 在 apple mock 中添加 getAccount 方法模拟
    $this->apple->shouldReceive('getAccount')->andReturn($mockAccount);

    // 模拟手机号
    $phone = Mockery::mock(Phone::class);
    $phone->shouldReceive('getAttribute')->with('country_code')->andReturn('CN');
    $phone->shouldReceive('getAttribute')->with('national_number')->andReturn('13800138000');
    $phone->shouldReceive('getAttribute')->with('country_dial_code')->andReturn('+86');
    $phone->shouldReceive('getAttribute')->with('phone_address')->andReturn('Test Address');
    $phone->shouldReceive('getAttribute')->with('phone')->andReturn('13800138000');

    // 设置 phone 属性
    $this->service->refreshAvailablePhone($phone);

    // 模拟完整的响应对象
    $mockResponse                          = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone::class
    );
    $phoneNumberVerification               = Mockery::mock(PhoneNumberVerification::class);
    $phoneNumber                           = Mockery::mock(PhoneNumber::class);
    $phoneNumber->id                       = 1;
    $phoneNumberVerification->phoneNumber  = $phoneNumber;
    $mockResponse->phoneNumberVerification = $phoneNumberVerification;

    // 模拟依赖
    $this->apple->shouldReceive('getWebResource->getAppleIdResource->getSecurityPhoneResource->securityVerifyPhone')
        ->andReturn($mockResponse);

    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->andReturn('123456');

    $this->dispatcher->shouldReceive('dispatch')
        ->with(Mockery::type(AccountBindPhoneSuccessEvent::class));

    // 添加 securityVerifyPhoneSecurityCode 的模拟
    $mockSecurityCodeResponse = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone::class
    );
    $this->apple->shouldReceive(
        'getWebResource->getAppleIdResource->getSecurityPhoneResource->securityVerifyPhoneSecurityCode'
    )
        ->withArgs(function ($id, $phoneNumber, $countryCode, $countryDialCode, $code) {
            return $id === 1
                && $phoneNumber === '13800138000'
                && $countryCode === 'CN'
                && $countryDialCode === '+86'
                && $code === '123456';
        })
        ->andReturn($mockSecurityCodeResponse);

    // 执行测试
    $result = $this->service->addSecurityVerifyPhone();

    // 验证结果
    expect($result)->toBeInstanceOf(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone::class
    );
});

// 测试验证码发送次数过多异常
test('验证码发送次数过多异常', function () {
    // 模拟手机号
    $phone = Mockery::mock(Phone::class);
    $phone->shouldReceive('getAttribute')->with('country_code')->andReturn('CN');
    $phone->shouldReceive('getAttribute')->with('national_number')->andReturn('13800138000');
    $phone->shouldReceive('getAttribute')->with('country_dial_code')->andReturn('+86');
    $this->service->refreshAvailablePhone($phone);

    // 正确构造异常实例
    $mockResponse = Mockery::mock(Response::class);
    $exception    = new VerificationCodeSentTooManyTimesException(
        response: $mockResponse,
        message: '验证码发送次数过多'
    );

    // 模拟依赖
    $this->apple->shouldReceive('getWebResource->getAppleIdResource->getSecurityPhoneResource->securityVerifyPhone')
        ->andThrow($exception);

    $this->dispatcher->shouldReceive('dispatch')
        ->with(Mockery::type(AccountBindPhoneFailEvent::class));

    // 执行测试并验证异常
    $this->service->addSecurityVerifyPhone();
})->throws(VerificationCodeSentTooManyTimesException::class);

// 测试手机号异常
test('手机号异常', function () {
    // 模拟手机号
    $phone = Mockery::mock(Phone::class);
    $phone->shouldReceive('getAttribute')->with('country_code')->andReturn('CN');
    $phone->shouldReceive('getAttribute')->with('national_number')->andReturn('13800138000');
    $phone->shouldReceive('getAttribute')->with('country_dial_code')->andReturn('+86');
    $this->service->refreshAvailablePhone($phone);

    // 正确构造异常实例
    $mockResponse = Mockery::mock(Response::class);
    $exception    = new PhoneException(
        response: $mockResponse,
        message: '手机号异常'
    );

    // 模拟依赖
    $this->apple->shouldReceive('getWebResource->getAppleIdResource->getSecurityPhoneResource->securityVerifyPhone')
        ->andThrow($exception);

    $this->dispatcher->shouldReceive('dispatch')
        ->with(Mockery::type(AccountBindPhoneFailEvent::class));

    // 执行测试并验证异常
    $this->service->addSecurityVerifyPhone();
})->throws(PhoneException::class);

// 测试手机号已存在异常
test('手机号已存在异常', function () {
    // 模拟手机号
    $phone = Mockery::mock(Phone::class);
    $phone->shouldReceive('getAttribute')->with('country_code')->andReturn('CN');
    $phone->shouldReceive('getAttribute')->with('national_number')->andReturn('13800138000');
    $phone->shouldReceive('getAttribute')->with('country_dial_code')->andReturn('+86');
    $this->service->refreshAvailablePhone($phone);

    // 正确构造异常实例
    $mockResponse = Mockery::mock(Response::class);
    $exception    = new PhoneNumberAlreadyExistsException(
        response: $mockResponse,
        message: '手机号已存在'
    );

    // 模拟依赖
    $this->apple->shouldReceive('getWebResource->getAppleIdResource->getSecurityPhoneResource->securityVerifyPhone')
        ->andThrow($exception);

    $this->dispatcher->shouldReceive('dispatch')
        ->with(Mockery::type(AccountBindPhoneFailEvent::class));

    // 执行测试并验证异常
    $this->service->addSecurityVerifyPhone();
})->throws(PhoneNumberAlreadyExistsException::class);


// 测试黑名单功能
test('黑名单功能', function () {
    // 创建基础 mock
    $mockService = Mockery::mock(TestAddSecurityVerifyPhoneService::class, [
        $this->apple,
        $this->phoneCodeService,
        $this->dispatcher,
        $this->logger,
    ]);


    // 设置部分模拟
    $mockService->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // 模拟手机号
    $phone = Mockery::mock(Phone::class);
    $phone->shouldReceive('getAttribute')->with('country_code')->andReturn('CN');
    $phone->shouldReceive('getAttribute')->with('national_number')->andReturn('13800138000');
    $phone->shouldReceive('getAttribute')->with('country_dial_code')->andReturn('+86');
    $phone->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $phone->shouldReceive('getAttribute')->with('phone_address')->andReturn('Test Address');
    $phone->shouldReceive('getAttribute')->with('phone')->andReturn('13800138000');
    $mockService->refreshAvailablePhone($phone);


    // 新增 Account 模拟
    $mockAccount = Mockery::mock(\Modules\AppleClient\Service\DataConstruct\Account::class);
    $mockAccount->shouldReceive('getAttribute')->with('account')->andReturn('test@example.com');
    $mockAccount->account = 'test@example.com';
    // 在 apple mock 中添加 getAccount 方法模拟
    $this->apple->shouldReceive('getAccount')->andReturn($mockAccount);

    // 构造验证码过多异常
    $mockResponse = Mockery::mock(Response::class);
    $exception    = new VerificationCodeSentTooManyTimesException(
        response: $mockResponse,
        message: '验证码发送次数过多'
    );

    // 模拟数据库更新
    $mockService->shouldReceive('updatePhoneInDatabase')
        ->times()
        ->andReturnTrue();

    $mockService->shouldReceive('refreshAvailablePhone')
        ->andReturn($phone);

    $mockService->shouldReceive('errorNotification')
        ->times()
        ->andReturn();


    // 模拟Redis操作
    Redis::shouldReceive('hset')
        ->once()
        ->with(
            AddSecurityVerifyPhoneService::PHONE_BLACKLIST_KEY,
            '1',
            Mockery::type('integer')
        );

    Redis::shouldReceive('expire')
        ->once()
        ->with(
            AddSecurityVerifyPhoneService::PHONE_BLACKLIST_KEY,
            AddSecurityVerifyPhoneService::BLACKLIST_EXPIRE_SECONDS
        );


    $mockService->shouldReceive('addSecurityVerifyPhone')
        ->times(1)
        ->andThrow($exception);

    // 模拟事件分发
    $this->dispatcher->shouldReceive('dispatch')
        ->with(Mockery::type(AccountBindPhoneFailEvent::class));

    try {

        $mockService->attemptBind();

    } catch (VerificationCodeSentTooManyTimesException $e) {
        // 验证Redis操作
        Redis::shouldHaveReceived('hset')->once();
        // 验证数据库更新
        $mockService->shouldReceive('updatePhoneInDatabase')->times(2);
    }
})->throws(MaxRetryAttemptsException::class);
