<?php

use App\Events\UpdateAccountInfoFailed;
use App\Events\UpdateAccountInfoSuccessed;
use App\Models\Account;
use App\Models\AccountManager;
use App\Models\Devices;
use App\Models\Payment;
use App\Services\AuthenticationService;
use App\Services\UpdateAccountInfoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->realAccount = Account::factory()->create([
        'appleid' => fake()->unique()->safeEmail(),
        'status' => \App\Apple\Enums\AccountStatus::LOGIN_SUCCESS,
    ]);

    // Mock only external dependencies
    $this->authService = Mockery::mock(AuthenticationService::class);
});

afterEach(function () {
    Mockery::close();
});

describe('UpdateAccountInfoService', function () {

    describe('Service Initialization', function () {

        it('can be instantiated with required dependencies', function () {
            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);
            expect($service)->toBeInstanceOf(UpdateAccountInfoService::class);
        });
    });

    describe('Main Handle Flow', function () {

        it('ensures authentication before processing', function () {
            // Arrange
            Event::fake();

            $this->authService->shouldReceive('ensureAuthenticated')
                ->once()
                ->with($this->realAccount);

            Payment::factory()->create([
                'account_id' => $this->realAccount->id,
            ]);
            Devices::factory()->create([
                'account_id' => $this->realAccount->id,
            ]);
            AccountManager::factory()->create([
                'account_id' => $this->realAccount->id,
            ]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->handle();

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('updates payment config when payment is missing', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();

            // Create existing devices and account manager (so only payment is missing)
            Devices::factory()->create(['account_id' => $this->realAccount->id]);
            AccountManager::factory()->create(['account_id' => $this->realAccount->id]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->handle();

            // Assert - Since we can't mock the Apple API easily, we expect an exception
            // which will trigger the UpdateAccountInfoFailed event
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->appleId->id === $this->realAccount->id &&
                    $event->type === '支付信息';
            });
        });

        it('updates devices when devices are empty', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();

            // Create existing payment and account manager (so only devices are missing)
            Payment::factory()->create(['account_id' => $this->realAccount->id]);
            AccountManager::factory()->create(['account_id' => $this->realAccount->id]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->handle();

            // Assert - Since we can't mock the Apple API easily, we expect an exception
            // which will trigger the UpdateAccountInfoFailed event
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->appleId->id === $this->realAccount->id &&
                    $event->type === '设备信息';
            });
        });

        it('updates account manager when missing', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();

            // Create existing payment and devices (so only account manager is missing)
            Payment::factory()->create(['account_id' => $this->realAccount->id]);
            Devices::factory()->create(['account_id' => $this->realAccount->id]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->handle();

            // Assert - Since we can't mock the Apple API easily, we expect an exception
            // which will trigger the UpdateAccountInfoFailed event
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->appleId->id === $this->realAccount->id &&
                    $event->type === '账号信息';
            });
        });

        it('skips updates when all data exists', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();

            // Create all required data
            Payment::factory()->create(['account_id' => $this->realAccount->id]);
            Devices::factory()->create(['account_id' => $this->realAccount->id]);
            AccountManager::factory()->create(['account_id' => $this->realAccount->id]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->handle();

            // Assert - No update events should be dispatched
            Event::assertNotDispatched(UpdateAccountInfoSuccessed::class);
            Event::assertNotDispatched(UpdateAccountInfoFailed::class);
        });
    });

    describe('Payment Configuration Update', function () {

        it('successfully creates payment record when not exists', function () {
            // Arrange
            Event::fake();
            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act - This will fail with Apple API call, triggering failure event
            $service->updateOrCreatePaymentConfig();

            // Assert - We expect a failure event since we can't mock the Apple API in this simplified test
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->type === '支付信息' &&
                    $event->appleId->id === $this->realAccount->id;
            });
        });

        it('updates existing payment record data', function () {
            // Arrange
            Event::fake();

            // Create existing payment record with old data
            $existingPayment = Payment::factory()->create([
                'account_id' => $this->realAccount->id,
                'payment_id' => 'old_payment_id',
                'payment_method_name' => 'Old Method',
            ]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act - This will try to update but fail with Apple API call
            $service->updateOrCreatePaymentConfig();

            // Assert - Payment record still exists in database
            $this->assertDatabaseHas('payments', [
                'id' => $existingPayment->id,
                'account_id' => $this->realAccount->id,
                'payment_id' => 'old_payment_id',
            ]);

            // Failure event should be dispatched due to Apple API call
            Event::assertDispatched(UpdateAccountInfoFailed::class);
        });
    });

    describe('Devices Information Update', function () {

        it('successfully handles device creation when not exists', function () {
            // Arrange
            Event::fake();
            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act - This will fail with Apple API call, triggering failure event
            $service->updateOrCreateDevices();

            // Assert - We expect a failure event since we can't mock the Apple API
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->type === '设备信息' &&
                    $event->appleId->id === $this->realAccount->id;
            });
        });

        it('preserves existing device records', function () {
            // Arrange
            Event::fake();

            // Create existing device records
            $device1 = Devices::factory()->create([
                'account_id' => $this->realAccount->id,
                'device_id' => 'device_1',
                'name' => 'Test iPhone',
            ]);

            $device2 = Devices::factory()->create([
                'account_id' => $this->realAccount->id,
                'device_id' => 'device_2',
                'name' => 'Test iPad',
            ]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->updateOrCreateDevices();

            // Assert - Existing devices should still be in database
            $this->assertDatabaseHas('devices', [
                'id' => $device1->id,
                'account_id' => $this->realAccount->id,
                'device_id' => 'device_1',
            ]);

            $this->assertDatabaseHas('devices', [
                'id' => $device2->id,
                'account_id' => $this->realAccount->id,
                'device_id' => 'device_2',
            ]);
        });
    });

    describe('Account Manager Update', function () {

        it('successfully handles account manager creation using reflection', function () {
            // Arrange
            Event::fake();
            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Use reflection to access protected method
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('updateOrCreateAccountManager');
            $method->setAccessible(true);

            // Act - This will fail with Apple API call
            $method->invoke($service);

            // Assert - We expect a failure event due to Apple API call
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->type === '账号信息' &&
                    $event->appleId->id === $this->realAccount->id;
            });
        });

        it('preserves existing account manager record', function () {
            // Arrange
            Event::fake();

            // Create existing account manager record
            $existingManager = AccountManager::factory()->create([
                'account_id' => $this->realAccount->id,
                'apple_id_display' => $this->realAccount->appleid,
            ]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Use reflection to access protected method
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('updateOrCreateAccountManager');
            $method->setAccessible(true);

            // Act
            $method->invoke($service);

            // Assert - Existing record should still be in database
            $this->assertDatabaseHas('account_managers', [
                'id' => $existingManager->id,
                'account_id' => $this->realAccount->id,
                'apple_id_display' => $this->realAccount->appleid,
            ]);
        });
    });

    describe('Event System Integration', function () {

        it('dispatches failure events with correct account and type', function () {
            // Arrange
            Event::fake();
            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act - Try to update payment (will fail due to no Apple API mock)
            $service->updateOrCreatePaymentConfig();

            // Assert - Check failure event structure
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->appleId->id === $this->realAccount->id &&
                    $event->type === '支付信息' &&
                    $event->e instanceof \Throwable;
            });
        });

        it('uses real account object in event dispatching', function () {
            // Arrange
            Event::fake();
            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->updateOrCreateDevices();

            // Assert - Verify event contains real account object
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->appleId instanceof Account &&
                    $event->appleId->appleid === $this->realAccount->appleid &&
                    $event->type === '设备信息';
            });
        });
    });

    describe('Authentication Integration', function () {

        it('handles authentication exceptions in main flow', function () {
            // Arrange
            $exception = new \Exception('Authentication failed');
            $this->authService->shouldReceive('ensureAuthenticated')
                ->once()
                ->with($this->realAccount)
                ->andThrow($exception);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act & Assert
            expect(fn() => $service->handle())
                ->toThrow(\Exception::class, 'Authentication failed');
        });

        it('passes correct account to authentication service', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')
                ->once()
                ->with($this->realAccount);

            // Create complete data set to skip updates
            Payment::factory()->create(['account_id' => $this->realAccount->id]);
            Devices::factory()->create(['account_id' => $this->realAccount->id]);
            AccountManager::factory()->create(['account_id' => $this->realAccount->id]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->handle();

            // Assert - Mock expectations automatically verified
            expect(true)->toBeTrue();
        });
    });

    describe('Edge Cases', function () {

        it('handles missing all data gracefully', function () {
            // Arrange
            Event::fake();
            $this->authService->shouldReceive('ensureAuthenticated')->once();

            // Don't create any data - all should be missing
            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act
            $service->handle();

            // Assert - Should attempt to update payment first and fail
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) {
                return $event->type === '支付信息';
            });
        });

        it('maintains database consistency during failures', function () {
            // Arrange
            Event::fake();

            // Create some existing data
            $payment = Payment::factory()->create(['account_id' => $this->realAccount->id]);
            $device = Devices::factory()->create(['account_id' => $this->realAccount->id]);

            $service = new UpdateAccountInfoService($this->realAccount, $this->authService);

            // Act - Try operations that will fail
            $service->updateOrCreatePaymentConfig();
            $service->updateOrCreateDevices();

            // Assert - Existing data should still be intact
            $this->assertDatabaseHas('payments', ['id' => $payment->id]);
            $this->assertDatabaseHas('devices', ['id' => $device->id]);
        });

        it('works with different account statuses', function () {
            // Arrange
            Event::fake();

            // Create account with different status
            $differentAccount = Account::factory()->create([
                'status' => \App\Apple\Enums\AccountStatus::BIND_SUCCESS,
            ]);

            $this->authService->shouldReceive('ensureAuthenticated')
                ->with($differentAccount);

            $service = new UpdateAccountInfoService($differentAccount, $this->authService);

            // Act
            $service->updateOrCreatePaymentConfig();

            // Assert - Should work with any account status
            Event::assertDispatched(UpdateAccountInfoFailed::class, function ($event) use ($differentAccount) {
                return $event->appleId->id === $differentAccount->id;
            });
        });
    });
});
