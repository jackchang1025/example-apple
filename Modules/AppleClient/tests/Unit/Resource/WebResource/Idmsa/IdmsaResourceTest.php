<?php

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Event;
use Modules\AppleClient\Events\AccountLoginFailEvent;
use Modules\AppleClient\Events\AccountLoginSuccessEvent;
use Modules\AppleClient\Events\SendPhoneSecurityCodeFailEvent;
use Modules\AppleClient\Events\SendVerificationCodeSuccessEvent;
use Modules\AppleClient\Events\VerifySecurityCodeFailEvent;
use Modules\AppleClient\Events\VerifySecurityCodeSuccessEvent;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\NullData;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Response\SignInInit;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendDeviceSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Resources\Web\Idmsa\IdmsaResource;
use Modules\AppleClient\Service\Resources\Web\WebResource;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Modules\PhoneCode\Service\PhoneCodeService;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;

uses(TestCase::class);

beforeEach(function () {
    Event::fake();

    $this->webResource      = Mockery::mock(WebResource::class)->makePartial();
    $this->phoneCodeService = Mockery::mock(PhoneCodeService::class);

    $this->account = Account::from([
        'account'          => 'test@example.com',
        'password'         => 'password123',
        'bindPhone'        => '13800138000',
        'bindPhoneAddress' => 'https://example.com/phone',
    ]);

    $this->apple = Mockery::mock(Apple::class);
    $this->apple->shouldReceive('getAccount')->andReturn($this->account);
    $this->apple->shouldReceive('getDispatcher')->andReturn(app('events'));
    $this->webResource->shouldReceive('getApple')->andReturn($this->apple);

    // 创建标准的手机号对象用于测试
    $this->testPhone = PhoneNumber::from([
        'id'                 => 1,
        'number'             => '13800138000',
        'numberWithDialCode' => '+86 138 0013 8000',
        'pushMode'           => 'sms',
        'obfuscatedNumber'   => '•••• ••00',
        'lastTwoDigits'      => '00',
    ]);


    $this->idmsaResource = Mockery::mock(IdmsaResource::class)->makePartial();
    $this->idmsaResource->__construct(
        $this->webResource,
        $this->phoneCodeService,
    );


    $this->idmsaResource->shouldReceive('getWebResource')->andReturn($this->webResource);
    $this->idmsaResource->shouldReceive('getPhoneCodeService')->andReturn($this->phoneCodeService);
    $this->idmsaResource->shouldAllowMockingProtectedMethods();
});

/**
 * 测试正常登录流程
 */
test('test successful sign in process', function () {
    // 模拟登录初始化
    $signInInitResponse = SignInInit::from([
        'value' => 'test_value',
        'key'   => 'test_key',
    ]);
    $this->webResource->shouldReceive('getAppleAuthenticationConnector->getAuthenticationResource->signInInit')
        ->once()
        ->with($this->account->getAccount())
        ->andReturn($signInInitResponse);

    // 模拟 IdmsaConnector 的 signInInit
    $idmsaSignInInitResponse = \Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInInit::from([
        'a'         => 'test_a',
        'b'         => 'test_b',
        'c'         => 'test_c',
        'salt'      => 'test_salt',
        'iteration' => 'test_iteration',
        'protocol'  => 'test_protocol',
    ]);
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->signInInit')
        ->once()
        ->andReturn($idmsaSignInInitResponse);

    // 模拟登录完成请求
    $signInCompleteResponse = \Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Response\SignInComplete::from(
        [
            'M1' => 'test_M1',
            'M2' => 'test_M2',
            'c'  => 'test_c',
        ]
    );
    $this->webResource->shouldReceive('getAppleAuthenticationConnector->getAuthenticationResource->signInComplete')
        ->once()
        ->andReturn($signInCompleteResponse);

    // 模拟登���完成响应
    $signInComplete = SignInComplete::from([
        'authType' => 'sha2',
    ]);
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->signInComplete')
        ->once()
        ->andReturn($signInComplete);

    // 执行登录
    $result = $this->idmsaResource->signIn();

    // 验证结果
    expect($result)->toBe($signInComplete);
    Event::assertDispatched(AccountLoginSuccessEvent::class, function ($event) {
        return $event->account->account === $this->account->account;
    });
});

/**
 * 测试录失败场景
 */
test('test sign in failure', function () {
    // 创建一个失败的响应
    $failedResponse = Mockery::mock(\Saloon\Http\Response::class);
    $failedResponse->shouldReceive('status')->andReturn(401);
    $failedResponse->shouldReceive('body')->andReturn('Login failed');

    // 模拟登录初始化失败
    $this->webResource->shouldReceive('getAppleAuthenticationConnector->getAuthenticationResource->signInInit')
        ->once()
        ->with($this->account->getAccount())
        ->andThrow(Mockery::mock(RequestException::class));

    // 执行登录并验证异常
    expect(fn() => $this->idmsaResource->signIn())->toThrow(RequestException::class);

    // 验证失败事件
    Event::assertDispatched(AccountLoginFailEvent::class, function ($event) {
        return $event->account->account === $this->account->account;
    });
});

test('send verification code success', function () {

    // 模拟发送验证码成功
    $sendDeviceSecurityCode = Mockery::mock(SendDeviceSecurityCode::class);
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->sendSecurityCode')
        ->once()
        ->andReturn($sendDeviceSecurityCode);

    // 执行发送验证码
    $result = $this->idmsaResource->sendVerificationCode();
    expect($result)->toBe($sendDeviceSecurityCode);
    Event::assertDispatched(SendVerificationCodeSuccessEvent::class, function ($event) {
        return $event->account->account === $this->account->account;
    });
});

/**
 * 测试验证码相关功能
 */
test('test verification code operations', function () {

    // 模拟验证码验证失败
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->verifyPhoneCode')
        ->once()
        ->with($this->testPhone->id, '123456')
        ->andThrow(Mockery::mock(VerificationCodeException::class));

    $this->idmsaResource->verifyPhoneVerificationCode($this->testPhone, '123456');

    Event::assertDispatched(VerifySecurityCodeFailEvent::class, function ($event) {
        return $event->account->account === $this->account->account;
    });
})->throws(VerificationCodeException::class);

/**
 * 测试手机验证码获取流程
 */
test('test phone verification code process', function () {
    // 模拟发送验证码成功
    $sendDeviceSecurityCode = Mockery::mock(SendDeviceSecurityCode::class);
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->sendPhoneSecurityCode')
        ->once()
        ->with($this->testPhone->id)
        ->andReturn($sendDeviceSecurityCode);

    // ��拟获取���证码成功
    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->once()
        ->with($this->account->bindPhoneAddress, Mockery::type(PhoneCodeParser::class))
        ->andReturn('123456');

    $this->idmsaResource->shouldAllowMockingProtectedMethods();
    $this->idmsaResource->shouldReceive('validatePhoneAddress')
        ->once()
        ->andReturn(true);

    $this->idmsaResource->shouldReceive('waitForCodeDelivery')
        ->once();

    $code = $this->idmsaResource->getPhoneVerificationCode($this->testPhone);
    expect($code)->toBe('123456');
});

/**
 * 测试获取验证���超时
 */
it('test get phone verification code timeout', function () {

    $this->phoneCodeService->shouldReceive('attemptGetPhoneCode')
        ->once()
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码超时'));

    $this->idmsaResource->shouldAllowMockingProtectedMethods();
    $this->idmsaResource->shouldReceive('validatePhoneAddress')
        ->once()
        ->andReturn(true);

    $this->idmsaResource->shouldReceive('waitForCodeDelivery')
        ->once();


    $this->idmsaResource->shouldReceive('sendPhoneSecurityCode')
        ->once();

    expect(fn() => $this->idmsaResource->getPhoneVerificationCode($this->testPhone))
        ->toThrow(AttemptBindPhoneCodeException::class);
});

/**
 * 测试验证码重试机制
 */
test('test verify phone verification code fail', function () {


    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->verifyPhoneCode')
        ->once()
        ->with($this->testPhone->id, '123456')
        ->andThrow(Mockery::mock(VerificationCodeException::class));

    expect(fn() => $this->idmsaResource->verifyPhoneVerificationCode($this->testPhone, '123456'))
        ->toThrow(VerificationCodeException::class);

    Event::assertDispatched(VerifySecurityCodeFailEvent::class, function ($event) {
        return $event->account->account === $this->account->account;
    });
});

/**
 * 测试错误场景
 */
test('test error scenarios', function () {

    $this->idmsaResource->shouldReceive('validatePhoneAddress')
        ->once()
        ->andReturn(false);

    expect(fn() => $this->idmsaResource->getPhoneVerificationCode($this->testPhone))
        ->toThrow(AccountException::class, '手机号地址无效');


    // 测试发送验证码次数过多
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->sendPhoneSecurityCode')
        ->once()
        ->with($this->testPhone->id)
        ->andThrow(Mockery::mock(VerificationCodeSentTooManyTimesException::class));

    expect(fn() => $this->idmsaResource->sendPhoneSecurityCode($this->testPhone))
        ->toThrow(VerificationCodeSentTooManyTimesException::class);

    Event::assertDispatched(SendPhoneSecurityCodeFailEvent::class, function ($event) {
        return $event->account->account === $this->account->account;
    });

//    // 测试未找到手机号
//    $this->idmsaResource->shouldReceive('getAuth->filterTrustedPhoneById')
//        ->once()
//        ->with($this->testPhone->id)
//        ->andReturnNull();
//
//    expect(fn() => $this->idmsaResource->verifyPhoneVerificationCode($this->testPhone, '123456'))
//        ->toThrow(PhoneNotFoundException::class, '未找到可信手机号');
});

/**
 * 测试两步验证流程
 */
test('test two factor authentication process', function () {
    // 模拟可信手机列表
    $trustedPhones = new DataCollection(PhoneNumber::class, [$this->testPhone]);

    // 模拟 filterTrustedPhone 方法
    $this->idmsaResource->shouldReceive('filterTrustedPhone')
        ->once()
        ->andReturn($trustedPhones);

    // 模拟获取验证码
    $this->idmsaResource->shouldReceive('getPhoneVerificationCode')
        ->once()
        ->with(Mockery::type(PhoneNumber::class))
        ->andReturn('123456');

    // 模拟验证验证码
    $verifyResponse = Mockery::mock(VerifyPhoneSecurityCode::class);
    $this->idmsaResource->shouldReceive('verifyPhoneVerificationCode')
        ->once()
        ->with(Mockery::type(PhoneNumber::class), '123456')
        ->andReturn($verifyResponse);

    // 设置重试次数
    $this->idmsaResource->shouldReceive('getTries')->andReturn(3);

    // 执行两步验证
    $result = $this->idmsaResource->twoFactorAuthentication();

    // 验证结果
    expect($result)->toBe($verifyResponse);
});

/**
 * 测试两步验证失败场景
 */
test('test two factor authentication failure scenarios', function () {
    // 测试未绑定手机号
    $this->account->bindPhone = null;
    expect(fn() => $this->idmsaResource->twoFactorAuthentication())
        ->toThrow(PhoneAddressException::class, '未绑定手机号');

    // 测试未绑定手机地址
    $this->account->bindPhone        = '13800138000';
    $this->account->bindPhoneAddress = null;
    expect(fn() => $this->idmsaResource->twoFactorAuthentication())
        ->toThrow(PhoneAddressException::class, '未绑定手机号地址');

    // 测试无可信手机号
    $this->account->bindPhoneAddress = 'https://example.com/phone';
    $this->idmsaResource->shouldReceive('filterTrustedPhone')
        ->once()
        ->andReturn(new DataCollection(PhoneNumber::class, []));

    expect(fn() => $this->idmsaResource->twoFactorAuthentication())
        ->toThrow(PhoneNotFoundException::class, '未找到可信手机号');

    // 测试验证码获取失败
    $trustedPhones = new DataCollection(PhoneNumber::class, [$this->testPhone]);

    $this->idmsaResource->shouldReceive('filterTrustedPhone')
        ->once()
        ->andReturn($trustedPhones);

    $this->idmsaResource->shouldReceive('getPhoneVerificationCode')
        ->times(3)
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码失败'));

    // 设置重试次数
    $this->idmsaResource->shouldReceive('getTries')->andReturn(3);


    expect(fn() => $this->idmsaResource->twoFactorAuthentication())
        ->toThrow(MaxRetryAttemptsException::class, '所有手机号验证均失败');
});

/**
 * 测试验证安全码功能
 */
test('test verify security code', function () {
    // 模拟验证成功
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->verifySecurityCode')
        ->once()
        ->with('123456')
        ->andReturn(new NullData());

    $result = $this->idmsaResource->verifySecurityCode('123456');
    expect($result)->toBeInstanceOf(NullData::class);

    Event::assertDispatched(VerifySecurityCodeSuccessEvent::class, function ($event) {
        return $event->account->account === $this->account->account
            && $event->code === '123456';
    });


    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->verifySecurityCode')
        ->once()
        ->with('wrong_code')
        ->andThrow(Mockery::mock(VerificationCodeException::class));

    expect(fn() => $this->idmsaResource->verifySecurityCode('wrong_code'))
        ->toThrow(VerificationCodeException::class);

    Event::assertDispatched(\Modules\AppleClient\Events\VerifySecurityCodeFailEvent::class, function ($event) {
        return $event->account->account === $this->account->account
            && $event->code === 'wrong_code';
    });
});

/**
 * 测试发送验证码失败场景
 */
test('test send verification code failure', function () {

    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->sendSecurityCode')
        ->once()
        ->andThrow(Mockery::mock(RequestException::class));

    expect(fn() => $this->idmsaResource->sendVerificationCode())
        ->toThrow(RequestException::class);

    Event::assertDispatched(\Modules\AppleClient\Events\SendVerificationCodeFailEvent::class, function ($event) {
        return $event->account->account === $this->account->account;
    });
});

/**
 * 测试 Auth 对象的获取和缓存
 */
test('test get auth object', function () {
    // 模拟首次获取
    $authResponse = Mockery::mock(\Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\Auth\Auth::class);
    $this->idmsaResource->shouldReceive('getIdmsaConnector->getAuthenticateResources->auth')
        ->once()
        ->andReturn($authResponse);

    $result = $this->idmsaResource->getAuth();
    expect($result)->toBe($authResponse);

    // 模拟缓存复用 - 不应该再次调用 auth 方法
    $this->idmsaResource->shouldNotReceive('getIdmsaConnector->getAuthenticateResources->auth');
    $cachedResult = $this->idmsaResource->getAuth();
    expect($cachedResult)->toBe($authResponse);
});

/**
 * 测试默认重试设置
 */
test('test default retry settings', function () {
    $idmsaResource = new IdmsaResource($this->webResource, $this->phoneCodeService);

    expect($idmsaResource->getTries())->toBe(5)
        ->and($idmsaResource->getRetryInterval())->toBe(1000)
        ->and($idmsaResource->getUseExponentialBackoff())->toBeTrue();
});

/**
 * 测试自定义重试设置
 */
test('test custom retry settings', function () {


    $idmsaResource = new IdmsaResource(
        $this->webResource,
        $this->phoneCodeService,
        retryInterval: 2000,
        useExponentialBackoff: false,
        tries: 3
    );

    expect($idmsaResource->getTries())->toBe(3)
        ->and($idmsaResource->getRetryInterval())->toBe(2000)
        ->and($idmsaResource->getUseExponentialBackoff())->toBeFalse();
});

/**
 * 测试重试机制的实际工作情况
 */
test('test retry mechanism in action', function () {

    // 设置重试参数
    $this->idmsaResource->withTries(5)
        ->withRetryInterval(1000)
        ->withUseExponentialBackoff(false);

    // 模拟可信手机列表
    $trustedPhones = new DataCollection(PhoneNumber::class, [$this->testPhone]);
    $this->idmsaResource->shouldReceive('filterTrustedPhone')
        ->once()
        ->andReturn($trustedPhones);

    // 模拟前三次获取验证码失败
    $this->idmsaResource->shouldReceive('getPhoneVerificationCode')
        ->times(3)
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码失败'));

    // 第四次成功
    $this->idmsaResource->shouldReceive('getPhoneVerificationCode')
        ->once()
        ->andReturn('123456');

    // 验证码验证成功
    $verifyResponse = Mockery::mock(VerifyPhoneSecurityCode::class);
    $this->idmsaResource->shouldReceive('verifyPhoneVerificationCode')
        ->once()
        ->with(Mockery::type(PhoneNumber::class), '123456')
        ->andReturn($verifyResponse);

    // 执行两步验证
    $result = $this->idmsaResource->twoFactorAuthentication();

    // 验证结果
    expect($result)->toBe($verifyResponse);
});

/**
 * 测试超过最大重试次数
 */
test('test exceeding max retry attempts', function () {
    // 设置最大重试次数为3

    $this->idmsaResource->withTries(3);

    $this->idmsaResource->shouldReceive('getWebResource')->andReturn($this->webResource);
    $this->idmsaResource->shouldReceive('getPhoneCodeService')->andReturn($this->phoneCodeService);

    // 模拟可手��列表
    $trustedPhones = new DataCollection(PhoneNumber::class, [$this->testPhone]);
    $this->idmsaResource->shouldReceive('filterTrustedPhone')
        ->once()
        ->andReturn($trustedPhones);

    // 模拟所有尝试都失败
    $this->idmsaResource->shouldReceive('getPhoneVerificationCode')
        ->times(3)
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码失败'));

    // 执行两步验证并期望抛出最大重试异常
    expect(fn() => $this->idmsaResource->twoFactorAuthentication())
        ->toThrow(MaxRetryAttemptsException::class, '所有手机号验证均失败');
});

/**
 * 测试重试间隔
 */
test('test retry interval', function () {
    $startTime = microtime(true);
    $this->idmsaResource->withTries(3)->withRetryInterval(1000)->withUseExponentialBackoff(false);

    $this->idmsaResource->shouldReceive('getWebResource')->andReturn($this->webResource);
    $this->idmsaResource->shouldReceive('getPhoneCodeService')->andReturn($this->phoneCodeService);

    // 模拟可信手机列表
    $trustedPhones = new DataCollection(PhoneNumber::class, [$this->testPhone]);
    $this->idmsaResource->shouldReceive('filterTrustedPhone')
        ->once()
        ->andReturn($trustedPhones);

    // 模拟所有尝试都失败
    $this->idmsaResource->shouldReceive('getPhoneVerificationCode')
        ->times(3)
        ->andThrow(new AttemptBindPhoneCodeException('获取验证码失败'));

    try {
        $this->idmsaResource->twoFactorAuthentication();
    } catch (MaxRetryAttemptsException $e) {
        $endTime  = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // 转换为毫秒

        // 验证总执行时间至少为 2000 毫秒 (2次重试 * 1000ms)
        expect($duration)->toBeGreaterThan(2000);
    }
});
