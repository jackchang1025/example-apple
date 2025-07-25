<?php

/**
 * @phpstan-ignore-next-line
 */

use App\Apple\Enums\AccountStatus;
use App\Jobs\AppleidAddSecurityVerifyPhone;
use App\Models\Account;
use App\Services\AddSecurityVerifyPhoneService;
use App\Services\AuthenticationService;
use App\Services\PhoneManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create([
        'appleid' => 'test@example.com',
        'status' => AccountStatus::LOGIN_SUCCESS,
        'bind_phone' => null,
    ]);

    $this->job = new AppleidAddSecurityVerifyPhone($this->account);
});

describe('AppleidAddSecurityVerifyPhone Job', function () {

    describe('Job Configuration', function () {

        it('has correct timeout configuration', function () {
            expect($this->job->timeout)->toBe(600); // 10 minutes
        });

        it('has correct tries configuration', function () {
            expect($this->job->tries)->toBe(6); // 6 attempts
        });

        it('has correct unique id format', function () {
            $uniqueId = $this->job->uniqueId();
            expect($uniqueId)->toBe("appleid_add_security_verify_phone_lock_{$this->account->appleid}");
        });

        it('has correct backoff configuration', function () {
            expect($this->job->backoff())->toBe(600); // 10 minutes
        });

        it('uses correct queue name', function () {
            expect($this->job->queue)->toBe('appleid_add_security_verify_phone');
        });
    });

    describe('Service Factory Method', function () {

        it('creates binding service with correct parameters', function () {
            // Arrange
            $phoneManager = app(PhoneManager::class);
            $authService = app(AuthenticationService::class);

            // Use reflection to test the protected factory method
            $method = makeMethodAccessible($this->job, 'createBindingService');
            $service = $method->invoke($this->job, $phoneManager, $authService);

            // Assert
            expect($service)->toBeInstanceOf(AddSecurityVerifyPhoneService::class);
        });
    });

    describe('Job Execution', function () {

        it('executes successfully when service completes without errors', function () {
            // Create testable job with mocked service
            $testJob = createTestableJob($this->account);

            $mockService = Mockery::mock(AddSecurityVerifyPhoneService::class);
            $mockService->shouldReceive('handle')->once()->andReturn();
            $testJob->setMockService($mockService);

            Log::shouldReceive('info')
                ->once()
                ->with('[BindAccountPhone] handle', ['appleid' => $this->account->appleid]);
            Log::shouldReceive('info')
                ->once()
                ->with(Mockery::pattern('/Successfully bound phone/'));
            Log::shouldReceive('error')->never();

            // Execute job
            $testJob->handle(
                Mockery::mock(PhoneManager::class),
                Mockery::mock(AuthenticationService::class)
            );

            expect(true)->toBeTrue();
        });

        it('logs error when service throws exception', function () {
            $testJob = createTestableJob($this->account);

            $exception = new Exception('Service error');
            $mockService = Mockery::mock(AddSecurityVerifyPhoneService::class);
            $mockService->shouldReceive('handle')->once()->andThrow($exception);
            $testJob->setMockService($mockService);

            Log::shouldReceive('info')
                ->once()
                ->with('[BindAccountPhone] handle', ['appleid' => $this->account->appleid]);
            Log::shouldReceive('error')
                ->once()
                ->with(Mockery::pattern('/Error binding phone/'));

            // Should not re-throw for generic exceptions
            $testJob->handle(
                Mockery::mock(PhoneManager::class),
                Mockery::mock(AuthenticationService::class)
            );

            expect(true)->toBeTrue();
        });
    });

    describe('Retry Behavior', function () {

        it('retries on SaloonException', function () {
            $testJob = createTestableJob($this->account);

            $saloonException = new \Saloon\Exceptions\SaloonException('Network error');
            $mockService = Mockery::mock(AddSecurityVerifyPhoneService::class);
            $mockService->shouldReceive('handle')->once()->andThrow($saloonException);
            $testJob->setMockService($mockService);

            Log::shouldReceive('info')
                ->once()
                ->with('[BindAccountPhone] handle', ['appleid' => $this->account->appleid]);
            Log::shouldReceive('error')
                ->once()
                ->with(Mockery::pattern('/Error binding phone/'));

            // Should re-throw exception (triggering retry)
            expect(fn() => $testJob->handle(
                Mockery::mock(PhoneManager::class),
                Mockery::mock(AuthenticationService::class)
            ))->toThrow(\Saloon\Exceptions\SaloonException::class);
        });

        it('does not retry on UnauthorizedException', function () {
            $testJob = createTestableJob($this->account);

            $mockResponse = Mockery::mock(\Saloon\Http\Response::class);
            $mockResponse->shouldReceive('status')->andReturn(401);
            $mockResponse->shouldReceive('body')->andReturn('Unauthorized');
            $unauthorizedException = new \Saloon\Exceptions\Request\Statuses\UnauthorizedException($mockResponse);

            $mockService = Mockery::mock(AddSecurityVerifyPhoneService::class);
            $mockService->shouldReceive('handle')->once()->andThrow($unauthorizedException);
            $testJob->setMockService($mockService);

            Log::shouldReceive('info')
                ->once()
                ->with('[BindAccountPhone] handle', ['appleid' => $this->account->appleid]);
            Log::shouldReceive('error')
                ->once()
                ->with(Mockery::pattern('/Error binding phone/'));

            // Should NOT re-throw (no retry)
            $testJob->handle(
                Mockery::mock(PhoneManager::class),
                Mockery::mock(AuthenticationService::class)
            );

            expect(true)->toBeTrue();
        });

        it('does not retry on StolenDeviceProtectionException', function () {
            $testJob = createTestableJob($this->account);

            $stolenDeviceException = new \Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException('Device protection');
            $mockService = Mockery::mock(AddSecurityVerifyPhoneService::class);
            $mockService->shouldReceive('handle')->once()->andThrow($stolenDeviceException);
            $testJob->setMockService($mockService);

            Log::shouldReceive('info')
                ->once()
                ->with('[BindAccountPhone] handle', ['appleid' => $this->account->appleid]);
            Log::shouldReceive('error')
                ->once()
                ->with(Mockery::pattern('/Error binding phone/'));

            // Should NOT re-throw (no retry)
            $testJob->handle(
                Mockery::mock(PhoneManager::class),
                Mockery::mock(AuthenticationService::class)
            );

            expect(true)->toBeTrue();
        });

        it('validates retry configuration', function () {
            expect($this->job->tries)->toBe(6); // 6 attempts
            expect($this->job->backoff())->toBe(600); // 10 minutes
            expect($this->job->timeout)->toBe(600); // 10 minutes
        });
    });

    describe('Unique Job Behavior', function () {

        it('generates correct unique id format', function () {
            $account = Account::factory()->create(['appleid' => 'test.lock@example.com']);
            $job = new AppleidAddSecurityVerifyPhone($account);

            $uniqueId = $job->uniqueId();

            expect($uniqueId)->toBe("appleid_add_security_verify_phone_lock_test.lock@example.com");
            expect($uniqueId)->toContain('appleid_add_security_verify_phone_lock_');
            expect($uniqueId)->toContain($account->appleid);
        });

        it('has different unique keys for different accounts', function () {
            $account1 = Account::factory()->create(['appleid' => 'user1@example.com']);
            $account2 = Account::factory()->create(['appleid' => 'user2@example.com']);

            $job1 = new AppleidAddSecurityVerifyPhone($account1);
            $job2 = new AppleidAddSecurityVerifyPhone($account2);

            expect($job1->uniqueId())->not->toBe($job2->uniqueId());
            expect($job1->uniqueId())->toContain('user1@example.com');
            expect($job2->uniqueId())->toContain('user2@example.com');
        });

        it('implements ShouldBeUnique interface', function () {
            expect($this->job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class);
            expect(method_exists($this->job, 'uniqueId'))->toBeTrue();
            expect(is_string($this->job->uniqueId()))->toBeTrue();
        });
    });

    describe('Account Status Scenarios', function () {

        it('works with different account statuses', function () {
            $statuses = [
                AccountStatus::BIND_SUCCESS,
                AccountStatus::BIND_ING,
                AccountStatus::BIND_FAIL,
                AccountStatus::BIND_RETRY,
                AccountStatus::LOGIN_SUCCESS,
            ];

            foreach ($statuses as $status) {
                $account = Account::factory()->create(['status' => $status]);
                $job = new AppleidAddSecurityVerifyPhone($account);

                expect($job->uniqueId())->toContain($account->appleid);
            }
        });
    });

    describe('Integration Tests', function () {

        it('can create binding service through real container', function () {
            $phoneManager = app(PhoneManager::class);
            $authService = app(AuthenticationService::class);

            // Test service creation through protected method
            $method = makeMethodAccessible($this->job, 'createBindingService');
            $service = $method->invoke($this->job, $phoneManager, $authService);

            expect($service)->toBeInstanceOf(AddSecurityVerifyPhoneService::class);
        });

        it('maintains job properties integrity', function () {
            expect($this->job->queue)->toBe('appleid_add_security_verify_phone');
            expect($this->job->timeout)->toBe(600);
            expect($this->job->tries)->toBe(6);
            expect($this->job->backoff())->toBe(600);
        });
    });
});

// Helper function to make private/protected methods accessible for testing
function makeMethodAccessible($object, $methodName)
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);
    return $method;
}

// Helper function to create testable job with mockable service
function createTestableJob($account)
{
    return new class($account) extends AppleidAddSecurityVerifyPhone {
        private $mockService;

        public function setMockService($service)
        {
            $this->mockService = $service;
        }

        protected function createBindingService($phoneManager, $authService): AddSecurityVerifyPhoneService
        {
            return $this->mockService ?: parent::createBindingService($phoneManager, $authService);
        }
    };
}
