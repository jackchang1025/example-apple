<?php

namespace Modules\AppleClient\Tests\Unit\Resource\WebResource\AppleId;

use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Exception\AppleClientException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\AccountManager;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Token\Token;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\ValidatePassword\ValidatePassword;
use Modules\AppleClient\Service\Resources\Web\AppleId\AccountManagerResource;
use Modules\AppleClient\Service\Resources\Web\AppleId\AppleIdResource;
use Modules\AppleClient\Service\Resources\Web\WebResource;
use Modules\AppleClient\Service\Apple;
use Saloon\Http\Response;

uses(TestCase::class);

beforeEach(function () {
    // 创建基础的 mock 对象
    $this->appleIdResource = Mockery::mock(AppleIdResource::class)->makePartial();

    // 创建 AccountManagerResource 实例
    $this->accountManagerResource = new AccountManagerResource($this->appleIdResource);

    // 创建测试账户
    $this->account = Account::from([
        'account'  => 'test@example.com',
        'password' => 'password123',
    ]);
});

/**
 * 测试获取 AppleIdResource
 */
test('test get apple id resource', function () {
    expect($this->accountManagerResource->getAppleIdResource())->toBe($this->appleIdResource);
});

/**
 * 测试密码认证成功场景
 */
test('test authenticate password success', function () {
    // 创建模拟对象
    $validatePassword = Mockery::mock(ValidatePassword::class);
    $apple            = Mockery::mock(Apple::class);
    $webResource      = Mockery::mock(WebResource::class);

    // 设置调用链期望
    $this->appleIdResource->shouldReceive('getWebResource')->andReturn($webResource);
    $webResource->shouldReceive('getApple')->andReturn($apple);
    $apple->shouldReceive('getAccount')->andReturn($this->account);

    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->authenticatePassword')
        ->once()
        ->with('password123')
        ->andReturn($validatePassword);

    // 执行密码认证
    $result = $this->accountManagerResource->authenticatePassword();

    // 验证结果
    expect($result)->toBe($validatePassword);
});

/**
 * 测试密码认证失败场景
 */
test('test authenticate password failure', function () {
    // 创建模拟对象
    $apple       = Mockery::mock(Apple::class);
    $webResource = Mockery::mock(WebResource::class);

    // 设置调用链期望
    $this->appleIdResource->shouldReceive('getWebResource')->andReturn($webResource);
    $webResource->shouldReceive('getApple')->andReturn($apple);
    $apple->shouldReceive('getAccount')->andReturn($this->account);

    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->authenticatePassword')
        ->once()
        ->with('password123')
        ->andThrow(Mockery::mock(AppleClientException::class));

    // 执行密码认证并期望抛出异常
    expect(fn() => $this->accountManagerResource->authenticatePassword())
        ->toThrow(AppleClientException::class);
});

/**
 * 测试获取令牌成功场景
 */
test('test get token success', function () {
    // 创建预期的响应对象
    $token = Mockery::mock(Token::class);

    // 设置 AppleIdConnector 的调用期望
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->token')
        ->once()
        ->andReturn($token);

    // 执行获取令牌
    $result = $this->accountManagerResource->token();

    // 验证结果
    expect($result)->toBe($token);
});

/**
 * 测试密码缓存功能
 */
test('test password caching', function () {
    // 创建预期的响应对象
    $validatePassword = Mockery::mock(ValidatePassword::class);

    // 创建模拟对象
    $apple       = Mockery::mock(Apple::class);
    $webResource = Mockery::mock(WebResource::class);

    // 设置调用链期望
    $this->appleIdResource->shouldReceive('getWebResource')->andReturn($webResource);
    $webResource->shouldReceive('getApple')->andReturn($apple);
    $apple->shouldReceive('getAccount')->andReturn($this->account);

    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->authenticatePassword')
        ->once()
        ->with('password123')
        ->andThrow($validatePassword);

    // 第一次调用
    $result1 = $this->accountManagerResource->authenticatePassword();

    // 第二次调用应该返回缓存的结果
    $result2 = $this->accountManagerResource->authenticatePassword();

    // 验证结果
    expect($result1)->toBe($validatePassword)
        ->and($result2)->toBe($validatePassword);
});

/**
 * 测试令牌缓存功能
 */
test('test token caching', function () {
    // 创建预期的响应对象
    $token = Mockery::mock(Token::class);

    // 设置 AppleIdConnector 的调用期望（只应该被调用一次）
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->token')
        ->once()
        ->andReturn($token);

    // 第一次调用
    $result1 = $this->accountManagerResource->token();

    // 第二次调用应该返回缓存的结果
    $result2 = $this->accountManagerResource->token();

    // 验证结果
    expect($result1)->toBe($token)
        ->and($result2)->toBe($token);
});

/**
 * 测试检查被盗设备保护 - 正常场景
 */
test('test is stolen device protection - normal case', function () {
    // 创建模拟对象
    $token               = Mockery::mock(Token::class);
    $validatePassword    = Mockery::mock(ValidatePassword::class);
    $securityVerifyPhone = Mockery::mock(SecurityVerifyPhone::class);
    $apple               = Mockery::mock(Apple::class);
    $webResource         = Mockery::mock(WebResource::class);

    // 设置调用链期望
    $this->appleIdResource->shouldReceive('getWebResource')->andReturn($webResource);
    $webResource->shouldReceive('getApple')->andReturn($apple);
    $apple->shouldReceive('getAccount')->andReturn($this->account);

    // Token 和密码认证的期望
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->token')
        ->once()
        ->andReturn($token);
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->authenticatePassword')
        ->once()
        ->with('password123')
        ->andReturn($validatePassword);

    // SecurityVerifyPhone 的期望
    $this->appleIdResource->shouldReceive('getSecurityPhoneResource->securityVerifyPhone')
        ->once()
        ->with('CN', '13800138000', '86')
        ->andReturn($securityVerifyPhone);

    // 执行检查
    $result = $this->accountManagerResource->isStolenDeviceProtectionException('CN', '13800138000', '86');

    // 验证结果
    expect($result)->toBe($securityVerifyPhone);
});

/**
 * 测试检查被盗设备保护 - 触发被盗设备保护场景
 */
test('test is stolen device protection - protection triggered', function () {
    // 创建模拟对象
    $token            = Mockery::mock(Token::class);
    $validatePassword = Mockery::mock(ValidatePassword::class);
    $apple            = Mockery::mock(Apple::class);
    $webResource      = Mockery::mock(WebResource::class);

    // 设置调用链期望
    $this->appleIdResource->shouldReceive('getWebResource')->andReturn($webResource);
    $webResource->shouldReceive('getApple')->andReturn($apple);
    $apple->shouldReceive('getAccount')->andReturn($this->account);

    // Token 和密码认证的期望
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->token')
        ->once()
        ->andReturn($token);
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->authenticatePassword')
        ->once()
        ->with('password123')
        ->andReturn($validatePassword);

    // SecurityVerifyPhone 抛出被盗设备保护异常
    $this->appleIdResource->shouldReceive('getSecurityPhoneResource->securityVerifyPhone')
        ->once()
        ->with('CN', '13800138000', '86')
        ->andThrow(Mockery::mock(StolenDeviceProtectionException::class));

    // 执行检查
    $result = $this->accountManagerResource->isStolenDeviceProtectionException('CN', '13800138000', '86');

    // 验证结果
    expect($result)->toBeTrue();
});

/**
 * 测试检查被盗设备保护 - 其他异常场景
 */
test('test is stolen device protection - other exception', function () {
    // 创建模拟对象
    $token            = Mockery::mock(Token::class);
    $validatePassword = Mockery::mock(ValidatePassword::class);
    $apple            = Mockery::mock(Apple::class);
    $webResource      = Mockery::mock(WebResource::class);

    // 设置调用链期望
    $this->appleIdResource->shouldReceive('getWebResource')->andReturn($webResource);
    $webResource->shouldReceive('getApple')->andReturn($apple);
    $apple->shouldReceive('getAccount')->andReturn($this->account);

    // Token 和密码认证的期望
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->token')
        ->once()
        ->andReturn($token);
    $this->appleIdResource->shouldReceive('getAppleIdConnector->getAuthenticateResources->authenticatePassword')
        ->once()
        ->with('password123')
        ->andReturn($validatePassword);

    // SecurityVerifyPhone 抛出其他异常
    $this->appleIdResource->shouldReceive('getSecurityPhoneResource->securityVerifyPhone')
        ->once()
        ->with('CN', '13800138000', '86')
        ->andThrow(Mockery::mock(AppleClientException::class));
    // 执行检查
    $result = $this->accountManagerResource->isStolenDeviceProtectionException('CN', '13800138000', '86');

    // 验证结果
    expect($result)->toBeFalse();
});
