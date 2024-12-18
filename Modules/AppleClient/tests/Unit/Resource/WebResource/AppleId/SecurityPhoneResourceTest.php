<?php

namespace Modules\AppleClient\Tests\Unit\Resource\WebResource\AppleId;

use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Modules\AppleClient\Events\AccountBindPhoneFailEvent;
use Modules\AppleClient\Events\AccountBindPhoneSuccessEvent;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\AddSecurityVerifyPhone\AddSecurityVerifyPhone;
use Modules\AppleClient\Service\DataConstruct\AddSecurityVerifyPhone\AddSecurityVerifyPhoneInterface;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Resources\Web\AppleId\AppleIdResource;
use Modules\AppleClient\Service\Resources\Web\AppleId\SecurityPhoneResource;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Modules\PhoneCode\Service\PhoneCodeService;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;

uses(TestCase::class);

beforeEach(function () {
    // 创建基础的 mock 对象
    $this->appleIdResource  = Mockery::mock(AppleIdResource::class);
    $this->phoneCodeService = Mockery::mock(PhoneCodeService::class);

    // 设置 AppleIdResource 的基本期望
    $this->appleIdResource->shouldReceive('getPhoneCodeService')
        ->andReturn($this->phoneCodeService);

    $this->securityPhoneResource = Mockery::mock(SecurityPhoneResource::class)->makePartial();
    $this->securityPhoneResource->__construct($this->appleIdResource);
    $this->securityPhoneResource->shouldReceive('getPhoneCodeService')
        ->andReturn($this->phoneCodeService);
});

/**
 * 测试默认重试设置
 */
test('test default retry settings', function () {
    $securityPhoneResource = new SecurityPhoneResource($this->appleIdResource);

    expect($securityPhoneResource->getTries())->toBe(5)
        ->and($securityPhoneResource->getRetryInterval())->toBe(1000)
        ->and($securityPhoneResource->getUseExponentialBackoff())->toBeTrue();
});

/**
 * 测试自定义重试设置
 */
test('test custom retry settings', function () {
    $this->securityPhoneResource->withTries(3)
        ->withRetryInterval(2000)
        ->withUseExponentialBackoff(false);

    expect($this->securityPhoneResource->getTries())->toBe(3)
        ->and($this->securityPhoneResource->getRetryInterval())->toBe(2000)
        ->and($this->securityPhoneResource->getUseExponentialBackoff())->toBeFalse();
});

/**
 * 测试基本的手机验证功能
 */
test('test basic security verify phone', function () {
    // 模拟手机验证响应
    $verifyResponse = Mockery::mock(SecurityVerifyPhone::class);

    // 设置期望的调用
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getSecurityPhoneResources->securityVerifyPhone')
        ->once()
        ->with('CN', '13800138000', '+86', true)
        ->andReturn($verifyResponse);

    // 执行验证
    $result = $this->securityPhoneResource->securityVerifyPhone(
        'CN',
        '13800138000',
        '+86'
    );

    // 验证结果
    expect($result)->toBe($verifyResponse);
});

/**
 * 测试添加安全手机号的完整流程
 */
test('test add security verify phone process', function () {


    $phoneNumber     = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumber::class
    );
    $phoneNumber->id = 1;

    $phoneNumberVerification              = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumberVerification::class
    );
    $phoneNumberVerification->phoneNumber = $phoneNumber;

    $securityVerifyPhone                          = Mockery::mock(SecurityVerifyPhone::class);
    $securityVerifyPhone->phoneNumberVerification = $phoneNumberVerification;

    // 设置初始验证的期望
    $this->securityPhoneResource->shouldReceive('securityVerifyPhone')
        ->once()
        ->with('CN', '13800138000', '+86')
        ->andReturn($securityVerifyPhone);

    // 模拟获取验证码
    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->once()
        ->with('https://example.com/phone', Mockery::type(PhoneCodeParser::class))
        ->andReturn('123456');

    // 模拟绑定成功的事件
    $this->appleIdResource->shouldReceive('getWebResource->getApple->getDispatcher->dispatch')
        ->once()
        ->with(Mockery::type(AccountBindPhoneSuccessEvent::class));

    $this->appleIdResource->shouldReceive('getWebResource->getApple->getAccount')
        ->once()
        ->andReturn(Mockery::mock(Account::class));

    // 模拟验证码验证响应
    $finalResponse = Mockery::mock(SecurityVerifyPhone::class);
    $this->securityPhoneResource->shouldReceive('securityVerifyPhoneSecurityCode')
        ->once()
        ->with(1, '13800138000', 'CN', '+86', '123456')
        ->andReturn($finalResponse);

    $addSecurityVerifyPhone = AddSecurityVerifyPhone::from([
        'countryCode'     => 'CN',
        'phoneNumber'     => '13800138000',
        'countryDialCode' => '+86',
        'phoneAddress'    => 'https://example.com/phone',
    ]);

    // 执行添加流程
    $result = $this->securityPhoneResource->addSecurityVerifyPhone($addSecurityVerifyPhone);

    // 验证结果
    expect($result)->toBe($finalResponse);
});

/**
 * 测试验证码验证失败的场景
 */
test('test security verify phone code failure', function () {


    $requestException = Mockery::mock(RequestException::class);
    $response         = Mockery::mock(Response::class);
    $response->shouldReceive('status')
        ->andReturn(400);

    $response->shouldReceive('body')
        ->andReturn('{"error": {"code": "INVALID_CODE"}}');

    $requestException->shouldReceive('getResponse')
        ->andReturn($response);

    // 设置验证码验证失败的期望
    $this->appleIdResource->shouldReceive(
        'getAppleIdConnector->getSecurityPhoneResources->securityVerifyPhoneSecurityCode'
    )
        ->once()
        ->andThrow($requestException);

    expect(fn() => $this->securityPhoneResource->securityVerifyPhoneSecurityCode(
        1,
        '13800138000',
        'CN',
        '+86',
        'wrong_code'
    ))->toThrow(VerificationCodeException::class);

    // 执行验证并期望抛出异常
});

/**
 * 测试重试机制的实际工作情况
 */
test('test retry mechanism in action', function () {
    // 设置重试参数
    $this->securityPhoneResource->withTries(5)
        ->withRetryInterval(1000)
        ->withUseExponentialBackoff(false);

    // 创建初始验证响应
    $phoneNumber     = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumber::class
    );
    $phoneNumber->id = 1;

    $phoneNumberVerification              = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumberVerification::class
    );
    $phoneNumberVerification->phoneNumber = $phoneNumber;

    $initialResponse                          = Mockery::mock(SecurityVerifyPhone::class);
    $initialResponse->phoneNumberVerification = $phoneNumberVerification;

    // 设置初始验证的期望
    $this->securityPhoneResource->shouldReceive('securityVerifyPhone')
        ->times(4)
        ->with('CN', '13800138000', '+86')
        ->andReturn($initialResponse);

    // 模拟绑定失败的事件
    $this->appleIdResource->shouldReceive('getWebResource->getApple->getDispatcher->dispatch')
        ->times(3)
        ->with(Mockery::type(AccountBindPhoneFailEvent::class));

    $this->appleIdResource->shouldReceive('getWebResource->getApple->getAccount')
        ->times(4)
        ->andReturn(Mockery::mock(Account::class));

    // 模拟前三次获取验证码失败
    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->times(3)
        ->with('https://example.com/phone', Mockery::type(PhoneCodeParser::class))
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码超时'));

    // 第四次成功
    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->once()
        ->with('https://example.com/phone', Mockery::type(PhoneCodeParser::class))
        ->andReturn('123456');

    $this->appleIdResource->shouldReceive('getWebResource->getApple->getDispatcher->dispatch')
        ->times(1)
        ->with(Mockery::type(AccountBindPhoneSuccessEvent::class));

    // 模拟最终验证成功
    $finalResponse = Mockery::mock(SecurityVerifyPhone::class);
    $this->securityPhoneResource->shouldReceive('securityVerifyPhoneSecurityCode')
        ->once()
        ->with(1, '13800138000', 'CN', '+86', '123456')
        ->andReturn($finalResponse);

    $addSecurityVerifyPhone = AddSecurityVerifyPhone::from([
        'countryCode'     => 'CN',
        'phoneNumber'     => '13800138000',
        'countryDialCode' => '+86',
        'phoneAddress'    => 'https://example.com/phone',
    ]);


    // 执行带重试的添加流程
    $result = $this->securityPhoneResource->addSecurityVerifyPhoneWithRetry($addSecurityVerifyPhone);

    // 验证结果
    expect($result)->toBe($finalResponse);
});

/**
 * 测试超过最大重试次数
 */
test('test exceeding max retry attempts', function () {
    // 设置最大重试次数为3
    $this->securityPhoneResource->withTries(3)
        ->withRetryInterval(1000)
        ->withUseExponentialBackoff(false);

    // 创建初始验证响应
    $phoneNumber     = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumber::class
    );
    $phoneNumber->id = 1;

    $phoneNumberVerification              = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumberVerification::class
    );
    $phoneNumberVerification->phoneNumber = $phoneNumber;

    $initialResponse                          = Mockery::mock(SecurityVerifyPhone::class);
    $initialResponse->phoneNumberVerification = $phoneNumberVerification;

    // 设置初始验证的期望
    $this->securityPhoneResource->shouldReceive('securityVerifyPhone')
        ->times(3)
        ->with('CN', '13800138000', '+86')
        ->andReturn($initialResponse);

    // 模拟所有获取验证码尝试都失败
    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->times(3)
        ->with('https://example.com/phone', Mockery::type(PhoneCodeParser::class))
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码超时'));

    // 模拟绑定失败的事件
    $this->appleIdResource->shouldReceive('getWebResource->getApple->getDispatcher->dispatch')
        ->times(3)
        ->with(Mockery::type(AccountBindPhoneFailEvent::class));

    $this->appleIdResource->shouldReceive('getWebResource->getApple->getAccount')
        ->times(3)
        ->andReturn(Mockery::mock(Account::class));

    $addSecurityVerifyPhone = AddSecurityVerifyPhone::from([
        'countryCode'     => 'CN',
        'phoneNumber'     => '13800138000',
        'countryDialCode' => '+86',
        'phoneAddress'    => 'https://example.com/phone',
    ]);

    $this->securityPhoneResource->addSecurityVerifyPhoneWithRetry($addSecurityVerifyPhone);

})->throws(AttemptBindPhoneCodeException::class);

/**
 * 测试重试间隔
 */
test('test retry interval', function () {
    $startTime = microtime(true);

    // 设置重试参数
    $this->securityPhoneResource->withTries(3)
        ->withRetryInterval(1000)
        ->withUseExponentialBackoff(false);

    // 创建初始验证响应
    $phoneNumber     = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumber::class
    );
    $phoneNumber->id = 1;

    $phoneNumberVerification              = Mockery::mock(
        \Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\PhoneNumberVerification::class
    );
    $phoneNumberVerification->phoneNumber = $phoneNumber;

    $initialResponse                          = Mockery::mock(SecurityVerifyPhone::class);
    $initialResponse->phoneNumberVerification = $phoneNumberVerification;

    // 设置初始验证的期望
    $this->securityPhoneResource->shouldReceive('securityVerifyPhone')
        ->times(3)
        ->with('CN', '13800138000', '+86')
        ->andReturn($initialResponse);

    // 模拟所有获取验证码尝试都失败
    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->times(3)
        ->with('https://example.com/phone', Mockery::type(PhoneCodeParser::class))
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码超时'));

    // 模拟绑定失败的事件
    $this->appleIdResource->shouldReceive('getWebResource->getApple->getDispatcher->dispatch')
        ->times(3)
        ->with(Mockery::type(AccountBindPhoneFailEvent::class));

    $this->appleIdResource->shouldReceive('getWebResource->getApple->getAccount')
        ->times(3)
        ->andReturn(Mockery::mock(Account::class));

    $addSecurityVerifyPhone = AddSecurityVerifyPhone::from([
        'countryCode'     => 'CN',
        'phoneNumber'     => '13800138000',
        'countryDialCode' => '+86',
        'phoneAddress'    => 'https://example.com/phone',
    ]);

    try {
        $this->securityPhoneResource->addSecurityVerifyPhoneWithRetry($addSecurityVerifyPhone);
    } catch (AttemptBindPhoneCodeException $e) {
        $endTime  = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // 转换为毫秒

        // 验证总执行时间至少为 2000 毫秒 (2次重试 * 1000ms)
        expect($duration)->toBeGreaterThan(2000);
    }
});
