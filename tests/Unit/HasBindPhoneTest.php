<?php

use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountBindPhoneSuccessEvent;
use App\Models\Account;
use App\Models\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Database\Factories\SecurityVerifyPhoneFactory;
use Modules\AppleClient\Service\AppleAccountManager;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\SecurityVerifyPhoneRequest;
use Modules\AppleClient\Service\Trait\HasBindPhone;
use Psr\Log\LoggerInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(TestCase::class);
uses(RefreshDatabase::class);

class TestAppleAccountManager extends AppleAccountManager
{
    use HasBindPhone;

    // 为了测试，我们暴露一些原本私有的方法
    public function publicHandleBindSuccess(): void
    {
        $this->handleBindSuccess();
    }

    public function publicHandleException(\Throwable $exception, int $attempt): void
    {
        $this->handleException($exception, $attempt);
    }

    public function publicHandlePhoneException(Throwable $exception): void
    {
        $this->handlePhoneException($exception);
    }

    public function publicBindPhoneToAccount(
    ): \Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone
    {
        return $this->bindPhoneToAccount();
    }
}

beforeEach(function () {

    $this->account = Account::factory()->create([
        'account'            => fake()->email(),
        'password'           => bcrypt('password'),
        'bind_phone'         => null,
        'bind_phone_address' => null,
    ]);
    $this->manager = new TestAppleAccountManager($this->account);

    $this->appleClient = Mockery::mock(\Modules\AppleClient\Service\AppleClient::class);

    $this->manager->withClient($this->appleClient);
});

test('validateAccount passes when account has no bound phone', function () {
    expect(fn() => $this->manager->validateAccount())->not->toThrow(AccountException::class);
});

test('validateAccount throws exception when account already has bound phone', function () {
    $this->account->update(['bind_phone' => '+1234567890']);
    expect(fn() => $this->manager->validateAccount())->toThrow(AccountException::class, '该账户已绑定手机号');
});

test('getAvailablePhone returns a valid phone', function () {
    Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);
    $phone = $this->manager->refreshAvailablePhone();
    expect($phone)->toBeInstanceOf(Phone::class)
        ->and($phone->status)->toBe(Phone::STATUS_BINDING);
});

test('getAvailablePhone throws exception when no available phone', function () {
    expect(fn() => $this->manager->refreshAvailablePhone())->toThrow(
        Illuminate\Database\Eloquent\ModelNotFoundException::class
    );
});

test('sendBindRequest returns PhoneNumberVerification object', function () {

    MockClient::global()->addResponse(
        MockResponse::make(
            body: (new SecurityVerifyPhoneFactory())->makeOne()->toArray(),
            status: 200
        ),
        SecurityVerifyPhoneRequest::class
    );

    $this->manager->withClient(new \Modules\AppleClient\Service\AppleClient());

    $result = $this->manager->securityVerifyPhone('US', '1234567890', '1');

    expect($result)->toBeInstanceOf(
        \Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone::class
    );
});

test('handleBindPhone successfully binds phone', function () {
    $phone = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);

    $mockManager         = Mockery::mock(TestAppleAccountManager::class)->makePartial();
    $SecurityVerifyPhone = (new SecurityVerifyPhoneFactory())->makeOne();
    $mockManager->shouldReceive('securityVerifyPhone')->once()->andReturn($SecurityVerifyPhone);

    $mockManager->shouldReceive('validateAccount')->once();
    $mockManager->shouldReceive('refreshAvailablePhone')->once()->andReturn($phone);

    $mockManager->shouldReceive('getPhoneConnector->attemptGetPhoneCode')->once()->andReturn('123456');
    $mockManager->shouldReceive('securityVerifyPhoneSecurityCode')->once();
    $mockManager->shouldReceive('getAccount')->times(5)->andReturn($this->account);

    $this->appleClient->shouldReceive('getTries')->times(1)->andReturn(3);
    $mockManager->withClient($this->appleClient);

    $log = Mockery::mock(LoggerInterface::class);
    $log->shouldReceive('info')->once();
//    $log->shouldReceive('error')->once();

    $mockManager->shouldReceive('getLogger')->once()->andReturn($log);

    Event::fake();

    $mockManager->handleBindPhone();

    Event::assertDispatched(AccountBindPhoneSuccessEvent::class);

    expect($mockManager->getAccount()->bind_phone)->toBe($phone->phone)
        ->and($mockManager->getAccount()->bind_phone_address)->toBe($phone->phone_address)
        ->and($phone->fresh()->status)->toBe(Phone::STATUS_BOUND);
});

test('handleBindPhone throws MaxRetryAttemptsException after multiple failures', function () {
    $mockManager = Mockery::mock(TestAppleAccountManager::class)->makePartial();
    $mockManager->shouldReceive('validateAccount')->once();
    $mockManager->shouldReceive('getAccount')->times(13)->andReturn($this->account);
    $mockManager->shouldReceive('refreshAvailablePhone')
        ->times(3)
        ->andThrow(new PhoneException(Mockery::mock(\Saloon\Http\Response::class), 'Test exception'));

    $this->appleClient->shouldReceive('getTries')->times(4)->andReturn(3);
    $mockManager->withClient($this->appleClient);

    $log = Mockery::mock(LoggerInterface::class);
//    $log->shouldReceive('info')->once();
    $log->shouldReceive('error')->times(4);

    $mockManager->shouldReceive('getLogger')->times(4)->andReturn($log);

    Event::fake();

    $mockManager->handleBindPhone();

    Event::assertDispatched(AccountBindPhoneFailEvent::class, 3);

})->throws(MaxRetryAttemptsException::class);

test('handlePhoneException marks phone as invalid on PhoneExceptions', function () {

    $phone = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);

    $this->manager->setPhone($phone);

    $this->manager->publicHandlePhoneException(
        new PhoneException(Mockery::mock(\Saloon\Http\Response::class), 'Test exception')
    );

    expect($phone->fresh()->status)->toBe(Phone::STATUS_INVALID)
        ->and($this->manager->getNotInPhones())->toContain($phone->id);
});

test('handlePhoneException marks phone as normal on other exceptions', function () {
    $phone = Phone::factory()->create(['status' => Phone::STATUS_BINDING]);
    $this->manager->setPhone($phone);

    $this->manager->publicHandlePhoneException(new \Exception('Test exception'));

    expect($phone->fresh()->status)->toBe(Phone::STATUS_NORMAL)
        ->and($this->manager->getNotInPhones())->toContain($phone->id);
});

test('refreshPhone updates phone property', function () {
    $phone  = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);
    $result = $this->manager->refreshAvailablePhone();

    expect($result)->toBeInstanceOf(Phone::class)
        ->and($this->manager->getPhone())->toBe($result)
        ->and($result?->id)->toBe($phone->id);
});

test('handleBindPhone rethrows StolenDeviceProtectionException', function () {
    $mockManager = Mockery::mock(TestAppleAccountManager::class)->makePartial();
    $mockManager->shouldReceive('validateAccount')->once();
    $mockManager->shouldReceive('getAvailablePhone')->once()->andReturn(Phone::factory()->create());
    $mockManager->shouldReceive('securityVerifyPhone')->once()->andThrow(
        new StolenDeviceProtectionException(
            Mockery::mock(\Saloon\Http\Response::class),
            'Stolen device protection active'
        )
    );
    $mockManager->shouldReceive('getAccount')->times(6)->andReturn($this->account);

    $log = Mockery::mock(LoggerInterface::class);
    $log->shouldReceive('error')->times(2);
    $mockManager->shouldReceive('getLogger')->times(2)->andReturn($log);
    $mockManager->shouldReceive('getClient')->times(0)->andReturn($this->appleClient);
    $mockManager->shouldReceive('getTries')->times(1)->andReturn(5);

    $mockManager->handleBindPhone();
})->throws(StolenDeviceProtectionException::class);

test('addNotInPhones adds phone id to notInPhones array', function () {
    $this->manager->addNotInPhones(1);
    $this->manager->addNotInPhones(2);

    expect($this->manager->getNotInPhones())->toBe([1, 2]);
});

test('setNotInPhones sets notInPhones array', function () {
    $this->manager->setNotInPhones([3, 4, 5]);

    expect($this->manager->getNotInPhones())->toBe([3, 4, 5]);
});
test('handlePhoneException marks phone as invalid on PhoneException', function () {
    $phone = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);
    $this->manager->setPhone($phone);

    $this->manager->publicHandlePhoneException(
        new PhoneException(Mockery::mock(\Saloon\Http\Response::class), 'Test exception')
    );

    expect($phone->fresh()->status)->toBe(Phone::STATUS_INVALID)
        ->and($this->manager->getNotInPhones())->toContain($phone->id);
});


test('bindPhoneToAccount successfully binds phone', function () {
    $phone = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);
    $this->manager->setPhone($phone);

    $mockManager         = Mockery::mock(TestAppleAccountManager::class)->makePartial();
    $SecurityVerifyPhone = (new SecurityVerifyPhoneFactory())->makeOne();
    $mockManager->shouldReceive('securityVerifyPhone')->once()->andReturn($SecurityVerifyPhone);

    $mockManager->shouldReceive('getPhoneConnector->attemptGetPhoneCode')->once()->andReturn('123456');

    $SecurityVerifyPhone = (new SecurityVerifyPhoneFactory())->makePhoneNumber();
    $mockManager->shouldReceive('securityVerifyPhoneSecurityCode')->once()->andReturn($SecurityVerifyPhone);

    $result = $mockManager->publicBindPhoneToAccount();

    expect($result)->toBeInstanceOf(
        \Modules\AppleClient\Service\DataConstruct\SecurityVerifyPhone\SecurityVerifyPhone::class
    );
});

test('handleBindSuccess updates account and phone status', function () {
    $phone = Phone::factory()->create(['status' => Phone::STATUS_BINDING]);
    $this->manager->setPhone($phone);

    $log = Mockery::mock(LoggerInterface::class);
    $log->shouldReceive('info')->times(1);

    $this->manager->withLogger($log);
    Event::fake();

    $this->manager->publicHandleBindSuccess();

    expect($this->manager->getAccount()->bind_phone)->toBe($phone->phone)
        ->and($this->manager->getAccount()->bind_phone_address)->toBe($phone->phone_address)
        ->and($phone->fresh()->status)->toBe(Phone::STATUS_BOUND);

    Event::assertDispatched(AccountBindPhoneSuccessEvent::class);
});
