<?php

use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginFailEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Models\Account;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\AppleAccountManager;
use Modules\AppleClient\Service\DataConstruct\Device\Devices;
use Modules\AppleClient\Service\DataConstruct\Payment\PaymentConfig;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\ProcessAccountImportService;
use Modules\PhoneCode\Service\PhoneConnector;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;

uses(TestCase::class);

beforeEach(function () {

    $this->account            = Account::factory()->create();
    $this->appleClientService = Mockery::mock(AppleAccountManager::class);

    $this->processAccountImportService = new ProcessAccountImportService($this->account, $this->appleClientService);

});

test('it can handle sign with invalid credentials', function () {
    $this->appleClientService->shouldReceive('authenticate')
        ->once()
        ->andThrow(
            new RequestException(
                Mockery::mock(Response::class),
                'test Exception'
            )
        );

    // 设置事件监听
    Event::fake();

    // 我们期望 ProcessAccountImportService::handle() 会重新抛出这个异常
    $this->expectException(RequestException::class);
    $this->expectExceptionMessage('test Exception');

    $this->processAccountImportService->handle();

    // 验证事件是否被触发
    Event::assertDispatched(AccountLoginFailEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === 'test Exception';
    });
});


it('handle authentication failure bind_phone', function () {

    $this->appleClientService->shouldReceive('authenticate')
        ->once()
        ->andReturn(true);

    $this->account->bind_phone = null;
    // 我们期望 ProcessAccountImportService::handle() 会重新抛出这个异常
    $this->expectException(AccountException::class);
    $this->expectExceptionMessage('未绑定手机号');

    // 设置事件监听
    Event::fake();

    $this->processAccountImportService->handle();

    // 验证事件是否被触发
    Event::assertDispatched(AccountLoginSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '登录成功';
    });
});

it('handle authentication failure', function () {

    $this->appleClientService->shouldReceive('authenticate')
        ->once()
        ->andReturn(true);

    $this->account->bind_phone_address = null;
    // 我们期望 ProcessAccountImportService::handle() 会重新抛出这个异常
    $this->expectException(AccountException::class);
    $this->expectExceptionMessage('未绑定手机号');

    // 设置事件监听
    Event::fake();

    $this->processAccountImportService->handle();

    // 验证事件是否被触发
    Event::assertDispatched(AccountLoginSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '登录成功';
    });
});

test('handle authentication failure phone address failed', function () {

    $this->appleClientService->shouldReceive('authenticate')->once()->andReturn(true);

    $phoneConnector = Mockery::mock(PhoneConnector::class);
    $phoneConnector
        ->shouldReceive('getPhoneCode')
        ->once()
        ->with($this->account->bind_phone_address)
        ->andThrow(Mockery::mock(RequestException::class));

    $this->appleClientService
        ->shouldReceive('getPhoneCodeService')
        ->once()
        ->andReturn($phoneConnector);

    $this->expectException(PhoneAddressException::class);
    $this->expectExceptionMessage('绑定手机号地址无效');

    // 设置事件监听
    Event::fake();
    $this->account->bind_phone = '+8613067772322';
    $this->processAccountImportService->handle();

    Event::assertDispatched(AccountLoginSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '登录成功';
    });

    // 验证事件是否被触发
    Event::assertDispatched(AccountAuthFailEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '绑定手机号地址无效';
    });

});

test('handle authentication failure PhoneNotFoundException', function () {

    $this->appleClientService
        ->shouldReceive('authenticate')
        ->once()
        ->andReturn(true);

    $phoneConnector = Mockery::mock(PhoneConnector::class);
    $phoneConnector->shouldReceive('getPhoneCode')
        ->times(1)
        ->with($this->account->bind_phone_address)
        ->andReturn(Mockery::mock(\Modules\AppleClient\Service\Response\Response::class));

    $this->appleClientService
        ->shouldReceive('getPhoneCodeService')
        ->times(1)
        ->andReturn($phoneConnector);

    $response = Mockery::mock(\Modules\AppleClient\Service\Response\Response::class);

    $phoneList = collect([
        new Phone(
            [
                'id'                 => '1',
                'numberWithDialCode' => '+86 ••• •••• ••21',
                'pushMode'           => 'SMS',
                'obfuscatedNumber'   => '+86*****21',
                'lastTwoDigits'      => '21',
            ]
        ),
    ]);

    $response->shouldReceive('getTrustedPhoneNumbers')
        ->once()
        ->andReturn($phoneList);

    $this->appleClientService
        ->shouldReceive('fetchAuthResponse')
        ->once()
        ->andReturn($response);

    $this->expectException(PhoneNotFoundException::class);
    $this->expectExceptionMessage('未找到该账号绑定的手机号码');

    // 设置事件监听
    Event::fake();
    $this->account->bind_phone = '+8613067772322';
    $this->processAccountImportService->handle();

    Event::assertDispatched(AccountLoginSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '登录成功';
    });

    // 验证事件是否被触发
    Event::assertDispatched(AccountAuthFailEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '未找到该账号绑定的手机号码';
    });

});


test('handle authentication success with  sendPhoneSecurityCode for hasTrustedDevices', function () {

    $code           = '123456';
    $phoneConnector = Mockery::mock(PhoneConnector::class);
    $phoneConnector->shouldReceive('attemptGetPhoneCode')
        ->once()
        ->andReturn($code);

    $phoneConnector->shouldReceive('getPhoneCode')
        ->times(1)
        ->with($this->account->bind_phone_address)
        ->andReturn(Mockery::mock(\Modules\AppleClient\Service\Response\Response::class));

    $this->appleClientService
        ->shouldReceive('authenticate')
        ->once()
        ->andReturn(true);

    $this->appleClientService->shouldReceive('sendPhoneSecurityCode')->once()->with('1')->andReturn(true);
    $this->appleClientService->shouldReceive('getPhoneCodeService')->times(2)->andReturn($phoneConnector);
    $this->appleClientService
        ->shouldReceive('verifyPhoneCode')
        ->once()->with('1', $code)
        ->andReturn(Mockery::mock(\Modules\AppleClient\Service\Response\Response::class));

    $this->appleClientService->shouldReceive('token')->once()->andReturn(true);

    $devices          = Mockery::mock(Devices::class);
    $devices->devices = Mockery::mock(\Spatie\LaravelData\DataCollection::class);
    $devices->devices->shouldReceive('map')->once();

    $this->appleClientService
        ->shouldReceive('getDevices')
        ->once()
        ->andReturn($devices);

    $paymentConfig = Mockery::mock(PaymentConfig::class);
//    $devices->devices = Mockery::mock(\Spatie\LaravelData\DataCollection::class);
//    $devices->devices->shouldReceive('map')->once();

    $this->appleClientService
        ->shouldReceive('getPayment')
        ->once()
        ->andReturn($paymentConfig);

    $response = Mockery::mock(\Modules\AppleClient\Service\Response\Response::class);

    $phoneList = collect([
        new Phone(
            [
                'id'                 => '1',
                'numberWithDialCode' => '+86 ••• •••• ••21',
                'pushMode'           => 'sms',
                'obfuscatedNumber'   => '••• •••• ••21',
                'lastTwoDigits'      => '21',
            ]
        ),
    ]);


    $response->shouldReceive('getTrustedPhoneNumbers')->once()->andReturn($phoneList);
    $response->shouldReceive('hasTrustedDevices')->once()->andReturn(true);

    $this->appleClientService->shouldReceive('fetchAuthResponse')->once()->andReturn($response);

    // 设置事件监听
    Event::fake();

    $this->account->bind_phone = '+8613067772321';

    $this->processAccountImportService->handle();

    Event::assertDispatched(AccountLoginSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '登录成功';
    });

    // 验证事件是否被触发
    Event::assertDispatched(AccountAuthSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '授权成功';
    });

});

test('handle authentication success with sendPhoneSecurityCode for phone', function () {

    $code           = '123456';
    $phoneConnector = Mockery::mock(PhoneConnector::class);
    $phoneConnector->shouldReceive('attemptGetPhoneCode')
        ->once()
        ->andReturn($code);

    $phoneConnector->shouldReceive('getPhoneCode')
        ->times(1)
        ->with($this->account->bind_phone_address)
        ->andReturn(Mockery::mock(\Modules\AppleClient\Service\Response\Response::class));

    $this->appleClientService->shouldReceive('authenticate')
        ->once()
        ->andReturn(true);

    $this->appleClientService
        ->shouldReceive('sendPhoneSecurityCode')
        ->once()
        ->with('2')
        ->andReturn(true);

    $this->appleClientService
        ->shouldReceive('getPhoneCodeService')
        ->times(2)
        ->andReturn($phoneConnector);

    $this->appleClientService
        ->shouldReceive('verifyPhoneCode')
        ->once()
        ->with('2', $code)
        ->andReturn(Mockery::mock(\Modules\AppleClient\Service\Response\Response::class));

    $this->appleClientService
        ->shouldReceive('token')
        ->once()
        ->andReturn(true);

    $devices          = Mockery::mock(Devices::class);
    $devices->devices = Mockery::mock(\Spatie\LaravelData\DataCollection::class);
    $devices->devices->shouldReceive('map')->once();
    $this->appleClientService
        ->shouldReceive('getDevices')
        ->once()
        ->andReturn($devices);

    $paymentConfig = Mockery::mock(PaymentConfig::class);
//    $devices->devices = Mockery::mock(\Spatie\LaravelData\DataCollection::class);
//    $devices->devices->shouldReceive('map')->once();

    $this->appleClientService
        ->shouldReceive('getPayment')
        ->once()
        ->andReturn($paymentConfig);

    $response = Mockery::mock(\Modules\AppleClient\Service\Response\Response::class);

    $phoneList = collect([
        new Phone(
            [
                'id'                 => '2',
                'numberWithDialCode' => '+86 ••• •••• ••21',
                'pushMode'           => 'sms',
                'obfuscatedNumber'   => '••• •••• ••21',
                'lastTwoDigits'      => '21',
            ]
        ),
        new Phone(
            [
                'id'                 => '3',
                'numberWithDialCode' => '+852 •••• ••67',
                'pushMode'           => 'sms',
                'obfuscatedNumber'   => '••• •••• ••67',
                'lastTwoDigits'      => '67',
            ]
        ),
    ]);

    $response->shouldReceive('getTrustedPhoneNumbers')->times(2)->andReturn($phoneList);
    $response->shouldReceive('hasTrustedDevices')->once()->andReturn(false);

    $this->appleClientService->shouldReceive('fetchAuthResponse')->once()->andReturn($response);

    // 设置事件监听
    Event::fake();

    $this->account->bind_phone = '+8613067772321';
    $this->processAccountImportService->handle();

    Event::assertDispatched(AccountLoginSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '登录成功';
    });

    // 验证事件是否被触发
    Event::assertDispatched(AccountAuthSuccessEvent::class, function ($event) {
        return $event->account === $this->account &&
            $event->description === '授权成功';
    });

});



