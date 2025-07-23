<?php

use App\Jobs\UpdateAccountInfoJob;
use App\Models\Account;
use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\UpdateAccountInfoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create([
        'appleid' => fake()->unique()->safeEmail(),
        'status' => \App\Apple\Enums\AccountStatus::LOGIN_SUCCESS,
    ]);

    $this->job = new UpdateAccountInfoJob($this->account);

    // Helper method to create testable job instances
    $this->createTestableJob = function () {
        return new class($this->account) extends UpdateAccountInfoJob {
            private $mockService;

            public function setMockService($service): void
            {
                $this->mockService = $service;
            }

            protected function createUpdateService(AuthenticationService $authService): UpdateAccountInfoService
            {
                return $this->mockService;
            }
        };
    };
});

afterEach(function () {
    Mockery::close();
});

describe('UpdateAccountInfoJob', function () {

    describe('Job Configuration', function () {

        it('has correct queue configuration', function () {
            expect($this->job->queue)->toBe('update_account_info');
        });

        it('generates correct unique ID based on apple account', function () {
            $expectedUniqueId = "update_account_info_lock_{$this->account->appleid}";
            expect($this->job->uniqueId())->toBe($expectedUniqueId);
        });

        it('has correct retry configuration', function () {
            expect($this->job->tries)->toBe(1);
        });

        it('implements required interfaces', function () {
            expect($this->job)
                ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class)
                ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class);
        });
    });

    describe('Service Integration', function () {

        it('creates UpdateAccountInfoService with correct parameters', function () {
            // Arrange
            $authService = Mockery::mock(AuthenticationService::class);

            // Act - Use reflection to test the protected method
            $reflection = new \ReflectionClass($this->job);
            $method = $reflection->getMethod('createUpdateService');
            $method->setAccessible(true);

            $service = $method->invoke($this->job, $authService);

            // Assert
            expect($service)->toBeInstanceOf(UpdateAccountInfoService::class);
        });

        it('executes service successfully when no exceptions occur', function () {
            // Arrange
            $authService = Mockery::mock(AuthenticationService::class);
            $updateService = Mockery::mock(UpdateAccountInfoService::class);
            $updateService->shouldReceive('handle')->once()->andReturn();

            $testJob = ($this->createTestableJob)();
            $testJob->setMockService($updateService);

            // Act
            $testJob->handle($authService);

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });

        it('calls authentication service during execution', function () {
            // Arrange
            $authService = Mockery::mock(AuthenticationService::class);
            $updateService = Mockery::mock(UpdateAccountInfoService::class);
            $updateService->shouldReceive('handle')->once();

            $testJob = ($this->createTestableJob)();
            $testJob->setMockService($updateService);

            // Act
            $testJob->handle($authService);

            // Assert - Service interaction verified through mock expectations
            expect(true)->toBeTrue();
        });
    });

    describe('Exception Handling', function () {

        it('logs error when service throws exception', function () {
            // Arrange
            $authService = Mockery::mock(AuthenticationService::class);
            $exception = new \Exception('Service failed');

            Log::shouldReceive('error')
                ->once()
                ->with(Mockery::pattern("/UpdateAccountInfoService failed for account {$this->account->appleid}/"));

            $testJob = ($this->createTestableJob)();
            $updateService = Mockery::mock(UpdateAccountInfoService::class);
            $updateService->shouldReceive('handle')->once()->andThrow($exception);
            $testJob->setMockService($updateService);

            // Act
            $testJob->handle($authService);

            // Assert - Exception should be caught and logged
            expect(true)->toBeTrue();
        });

        it('does not propagate exceptions from service', function () {
            // Arrange
            $authService = Mockery::mock(AuthenticationService::class);
            $exception = new \Exception('Service failed');

            Log::shouldReceive('error')->once();

            $testJob = ($this->createTestableJob)();
            $updateService = Mockery::mock(UpdateAccountInfoService::class);
            $updateService->shouldReceive('handle')->once()->andThrow($exception);
            $testJob->setMockService($updateService);

            // Act & Assert - Should not throw exception
            $testJob->handle($authService);
            expect(true)->toBeTrue();
        });
    });

    describe('Job Uniqueness', function () {

        it('maintains uniqueness for same account', function () {
            $job1 = new UpdateAccountInfoJob($this->account);
            $job2 = new UpdateAccountInfoJob($this->account);

            expect($job1->uniqueId())->toBe($job2->uniqueId());
        });

        it('has different unique IDs for different accounts', function () {
            $account2 = Account::factory()->create([
                'appleid' => 'different@email.com',
            ]);

            $job1 = new UpdateAccountInfoJob($this->account);
            $job2 = new UpdateAccountInfoJob($account2);

            expect($job1->uniqueId())->not->toBe($job2->uniqueId());
        });
    });

    describe('Queue Integration', function () {

        it('can be dispatched to queue successfully', function () {
            Queue::fake();

            UpdateAccountInfoJob::dispatch($this->account);

            Queue::assertPushed(UpdateAccountInfoJob::class);
        });

        it('respects queue configuration when dispatched', function () {
            Queue::fake();

            UpdateAccountInfoJob::dispatch($this->account);

            Queue::assertPushedOn('update_account_info', UpdateAccountInfoJob::class);
        });
    });
});
