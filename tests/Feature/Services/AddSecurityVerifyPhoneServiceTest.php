<?php

/**
 * @phpstan-ignore-next-line
 */

use App\Apple\Enums\AccountStatus;
use App\Events\PhoneBinding\PhoneBindingFailed;
use App\Events\PhoneBinding\PhoneBindingStarted;
use App\Events\PhoneBinding\PhoneBindingSucceeded;
use App\Models\Account;
use App\Models\Phone;
use App\Services\AddSecurityVerifyPhoneService;
use App\Services\AuthenticationService;
use App\Services\PhoneManager;
use App\Services\PhoneVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Weijiajia\SaloonphpAppleClient\Exception\MaxRetryAttemptsException;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneException;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneNumberAlreadyExistsException;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeSentTooManyTimesException;
use App\Services\Integrations\Phone\Exception\AttemptGetPhoneCodeException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use App\Services\Integrations\Phone\Exception\InvalidPhoneException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create([
        'appleid' => fake()->unique()->safeEmail(),
        'status' => AccountStatus::LOGIN_SUCCESS,
        'bind_phone' => null,
    ]);

    $this->phone = Phone::factory()->create([
        'status' => Phone::STATUS_NORMAL,
        'phone' => fake()->e164PhoneNumber(),
        'phone_address' => fake()->unique()->url(),
    ]);

    // Mock dependencies with default expectations
    $this->phoneManager = Mockery::mock(PhoneManager::class);
    $this->phoneManager->shouldReceive('handlePhoneException')->byDefault();

    $this->authService = Mockery::mock(AuthenticationService::class);

    $this->phoneVerificationService = Mockery::mock(PhoneVerificationService::class);

    $this->service = new AddSecurityVerifyPhoneService(
        $this->account,
        $this->phoneManager,
        $this->authService,
        $this->phoneVerificationService
    );
});

describe('AddSecurityVerifyPhoneService', function () {

    describe('Service Initialization', function () {

        it('can be instantiated with required dependencies', function () {
            expect($this->service)->toBeInstanceOf(AddSecurityVerifyPhoneService::class);
            expect($this->service->getAccount())->toBe($this->account);
            expect($this->service->getCurrentPhone())->toBeNull();
            expect($this->service->getCurrentAttempt())->toBe(1);
        });

        it('has correct constants defined', function () {
            $reflection = new ReflectionClass(AddSecurityVerifyPhoneService::class);
            $maxAttempts = $reflection->getConstant('MAX_BIND_ATTEMPTS');
            expect($maxAttempts)->toBe(5);
        });
    });

    describe('Binding Skip Logic', function () {

        it('skips binding when account status is BIND_SUCCESS', function () {
            // Arrange
            $this->account->update(['status' => AccountStatus::BIND_SUCCESS]);
            Event::fake();

            // Act
            $this->service->handle();

            // Assert
            Event::assertNotDispatched(PhoneBindingStarted::class);
        });

        it('skips binding when account already has bind_phone', function () {
            // Arrange
            $this->account->update(['bind_phone' => '+1234567890']);
            Event::fake();

            // Act
            $this->service->handle();

            // Assert
            Event::assertNotDispatched(PhoneBindingStarted::class);
        });

        it('skips binding when account status is BIND_ING', function () {
            // Arrange
            $this->account->update(['status' => AccountStatus::BIND_ING]);
            Event::fake();

            // Act
            $this->service->handle();

            // Assert
            Event::assertNotDispatched(PhoneBindingStarted::class);
        });

        it('proceeds with binding when conditions are met', function () {
            // Arrange
            $this->account->update([
                'status' => AccountStatus::LOGIN_SUCCESS,
                'bind_phone' => null
            ]);

            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();
            $this->phoneVerificationService->shouldReceive('verify')->once()->with($this->phone)->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingStarted::class);
            Event::assertDispatched(PhoneBindingSucceeded::class);
        });
    });

    describe('Authentication Flow', function () {

        it('ensures authentication before starting binding process', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')
                ->once()
                ->with($this->account);
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            $this->phoneVerificationService->shouldReceive('verify')->once()->with($this->phone)->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('handles authentication failure gracefully', function () {
            // Arrange
            Event::fake();
            $authException = new Exception('Authentication failed');
            $this->authService->shouldReceive('ensureAuthenticated')
                ->once()
                ->andThrow($authException);

            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(Exception::class, 'Authentication failed');

            Event::assertDispatched(PhoneBindingStarted::class);
            Event::assertDispatched(PhoneBindingFailed::class, function ($event) use ($authException) {
                return $event->account->is($this->account)
                    && $event->exception === $authException
                    && $event->attempt === 1;
            });
        });
    });

    describe('Phone Binding Process', function () {

        it('successfully completes binding process on first attempt', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once()->with($this->phone);
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->with($this->phone)
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingStarted::class);
            Event::assertDispatched(PhoneBindingSucceeded::class, function ($event) {
                return $event->account->is($this->account)
                    && $event->phone->is($this->phone)
                    && $event->attempt === 1;
            });
            expect($this->service->getCurrentPhone())->toBe($this->phone);
        });

        it('retries on recoverable phone exceptions', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();

            // First attempt fails, second succeeds
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->twice()
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow(new VerificationCodeException('Code failed'));
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingSucceeded::class, function ($event) {
                return $event->attempt === 2;
            });
        });

        it('handles maximum retry attempts', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->times(5) // MAX_BIND_ATTEMPTS
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->times(5);

            $this->phoneVerificationService->shouldReceive('verify')
                ->times(5)
                ->andThrow(new PhoneException('Phone unavailable'));

            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(MaxRetryAttemptsException::class, '达到最大重试次数，绑定失败。');

            Event::assertDispatched(PhoneBindingFailed::class, function ($event) {
                return $event->exception instanceof MaxRetryAttemptsException;
            });
        });

        it('tracks current attempt number correctly', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->times(3)
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->times(2);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            $this->phoneVerificationService->shouldReceive('verify')
                ->twice()
                ->andThrow(new InvalidPhoneException('Too many attempts'));
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingSucceeded::class, function ($event) {
                return $event->attempt === 3;
            });
            expect($this->service->getCurrentAttempt())->toBe(3);
        });
    });

    describe('Exception Handling', function () {

        it('handles StolenDeviceProtectionException as fatal error', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();

            $stolenDeviceException = new StolenDeviceProtectionException('Device protection enabled');
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow($stolenDeviceException);

            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(StolenDeviceProtectionException::class);

            Event::assertDispatched(PhoneBindingFailed::class, function ($event) use ($stolenDeviceException) {
                return $event->exception === $stolenDeviceException;
            });
        });


        
        it('handles VerificationCodeSentTooManyTimesException as fatal error', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();

            $verificationCodeSentTooManyTimesException = new VerificationCodeSentTooManyTimesException('Too many attempts');
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow($verificationCodeSentTooManyTimesException);

            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(VerificationCodeSentTooManyTimesException::class);

            Event::assertDispatched(PhoneBindingFailed::class, function ($event) use ($verificationCodeSentTooManyTimesException) {
                return $event->exception === $verificationCodeSentTooManyTimesException;
            });
        });

        it('handles VerificationCodeException', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->twice()
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow(new VerificationCodeException('Code failed'));

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingSucceeded::class);
        });

        it('handles PhoneException', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->twice()
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow(new PhoneException('Phone error'));
                
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingSucceeded::class);
        });

        it('handles unexpected exceptions as fatal errors', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();

            $unexpectedException = new RuntimeException('Unexpected error');
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow($unexpectedException);

            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(RuntimeException::class);

            Event::assertDispatched(PhoneBindingFailed::class, function ($event) use ($unexpectedException) {
                return $event->exception === $unexpectedException;
            });
        });
    });

    describe('Event System Integration', function () {

        it('dispatches PhoneBindingStarted event at process start', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->with($this->phone)
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingStarted::class, function ($event) {
                return $event->account->is($this->account);
            });
        });

        it('dispatches PhoneBindingSucceeded event on successful completion', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->with($this->phone)
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            Event::assertDispatched(PhoneBindingSucceeded::class, function ($event) {
                return $event->account->is($this->account)
                    && $event->phone->is($this->phone)
                    && $event->attempt === 1;
            });
        });

        it('dispatches PhoneBindingFailed event on any failure', function () {
            // Arrange
            Event::fake();
            $exception = new Exception('Test failure');
            $this->authService->shouldReceive('ensureAuthenticated')->once()->andThrow($exception);

            // Act
            expect(fn() => $this->service->handle())->toThrow(Exception::class);

            // Assert
            Event::assertDispatched(PhoneBindingFailed::class, function ($event) use ($exception) {
                return $event->account->is($this->account)
                    && $event->exception === $exception
                    && $event->attempt === 1
                    && $event->phone === null;
            });
        });

        it('includes phone in failed event when available', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();

            $exception = new StolenDeviceProtectionException('Protection enabled');

            $this->phoneVerificationService->shouldReceive('verify')->once()->andThrow($exception);


            // Act
            expect(fn() => $this->service->handle())->toThrow(StolenDeviceProtectionException::class);

            // Assert
            Event::assertDispatched(PhoneBindingFailed::class, function ($event) use ($exception) {
                return $event->account->is($this->account)
                    && $event->exception === $exception
                    && $event->phone?->is($this->phone);
            });
        });
    });

    describe('State Management', function () {

        it('updates current phone during binding process', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert
            expect($this->service->getCurrentPhone())->toBe($this->phone);
        });

        it('updates current attempt counter correctly', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->times(2)
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();


            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow(new VerificationCodeException('First attempt fails'));
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));


            // Act
            $this->service->handle();

            // Assert
            expect($this->service->getCurrentAttempt())->toBe(2);
        });

        it('refreshes account data before checking skip conditions', function () {
            // This test verifies that account->refresh() is called
            // We can't easily mock this, but we can test the behavior indirectly

            // Arrange - Update account status in database
            $this->account->update(['status' => AccountStatus::LOGIN_SUCCESS]);

            // Modify the account object in memory to a different status
            $this->account->status = AccountStatus::BIND_SUCCESS;

            Event::fake();

            // Act - The service should call refresh() and see the real database status
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->with($this->phone)
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            $this->service->handle();

            // Assert - Binding should proceed because database status is LOGIN_SUCCESS
            Event::assertDispatched(PhoneBindingStarted::class);
        });
    });

    describe('Integration with Dependencies', function () {

        it('integrates correctly with PhoneManager for phone lifecycle', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();

            // Verify correct interaction with PhoneManager
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->once()
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')
                ->once()
                ->with($this->phone);

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->with($this->phone)
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('integrates correctly with PhoneManager for exception handling', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->twice()
                ->andReturn($this->phone);

            // Verify exception is passed to PhoneManager
            $exception = new PhoneException('Phone error');
            $this->phoneManager->shouldReceive('handlePhoneException')
                ->once()
                ->with($this->phone, $exception);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            // First call throws exception, second call succeeds
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andThrow($exception);
            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));

            // Act
            $this->service->handle();

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('correctly instantiates and uses PhoneVerificationService', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            // Verify PhoneVerificationService is created with correct account

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->with($this->phone)
                ->andReturn(Mockery::mock(SecurityVerifyPhone::class));


            // Act
            $this->service->handle();

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });
    });

    describe('Edge Cases', function () {

        it('handles null phone from PhoneManager gracefully', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->once()
                ->andThrow(new ModelNotFoundException('No available phones'));

            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(ModelNotFoundException::class);

            Event::assertDispatched(PhoneBindingFailed::class);
        });

        it('handles concurrent access scenarios', function () {
            // This test ensures the service behaves correctly when multiple instances
            // might be processing the same account simultaneously

            // Arrange
            $this->account->update(['status' => AccountStatus::BIND_ING]);
            Event::fake();

            // Act - Should skip because status is BIND_ING
            $this->service->handle();

            // Assert
            Event::assertNotDispatched(PhoneBindingStarted::class);
        });

        it('maintains consistency when account is deleted during process', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);

            // Delete account during verification to simulate race condition

            $this->phoneVerificationService->shouldReceive('verify')
                ->once()
                ->andReturnUsing(function () {
                    $this->account->delete();
                    return Mockery::mock(SecurityVerifyPhone::class);
                });

            $this->phoneManager->shouldReceive('markPhoneAsBound')->once();

            // Act
            $this->service->handle();

            // Assert - Service should complete successfully
            Event::assertDispatched(PhoneBindingSucceeded::class);
        });
    });

    describe('Performance Considerations', function () {

        it('limits retry attempts to prevent infinite loops', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')
                ->times(5) // Exactly MAX_BIND_ATTEMPTS times
                ->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->times(5);


            $this->phoneVerificationService->shouldReceive('verify')
                ->times(5)
                ->andThrow(new VerificationCodeException('Always fails'));


            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(MaxRetryAttemptsException::class);
        });

        it('stops retrying on fatal exceptions', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();
            $this->phoneManager->shouldReceive('getAvailablePhone')->once()->andReturn($this->phone);
            $this->phoneManager->shouldReceive('handlePhoneException')->once();


            $this->phoneVerificationService->shouldReceive('verify')
                ->once() // Should only be called once, not retry
                ->andThrow(new StolenDeviceProtectionException('Fatal error'));


            // Act & Assert
            expect(fn() => $this->service->handle())
                ->toThrow(StolenDeviceProtectionException::class);
        });
    });
});
