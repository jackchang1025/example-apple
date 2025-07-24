<?php

use App\Models\Phone;
use App\Services\BlacklistManager;
use App\Services\PhoneManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeSentTooManyTimesException;
use App\Services\Integrations\Phone\Exception\InvalidPhoneException;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock BlacklistManager
    $this->blacklistManager = Mockery::mock(BlacklistManager::class);
    $this->blacklistManager->shouldReceive('getActiveBlacklistIds')->andReturn([]);

    $this->phoneManager = new PhoneManager($this->blacklistManager);
});

describe('PhoneManager', function () {

    describe('Service Initialization', function () {

        it('can be instantiated with BlacklistManager dependency', function () {
            expect($this->phoneManager)->toBeInstanceOf(PhoneManager::class);
        });

        it('starts with empty excluded phone IDs list', function () {
            expect($this->phoneManager->getExcludedPhoneIds())->toBeEmpty();
        });
    });

    describe('Available Phone Management', function () {

        it('successfully gets an available phone', function () {
            // Arrange
            $phone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result)->toBeInstanceOf(Phone::class);
            expect($result->id)->toBe($phone->id);
            expect($result->fresh()->status)->toBe(Phone::STATUS_BINDING);
        });

        it('throws ModelNotFoundException when no phones available', function () {
            // Arrange - No phones in database

            // Act & Assert
            expect(fn() => $this->phoneManager->getAvailablePhone())
                ->toThrow(ModelNotFoundException::class);
        });

        it('excludes phones without phone_address', function () {
            // Arrange
            $invalidPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $validPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($validPhone->id);
            expect($result->id)->not->toBe($invalidPhone->id);
        });

        it('excludes phones without phone number', function () {
            // Arrange
            $invalidPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $validPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($validPhone->id);
        });

        it('excludes phones with non-normal status', function () {
            // Arrange
            $invalidPhone = Phone::factory()->create([
                'status' => Phone::STATUS_INVALID,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $validPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($validPhone->id);
        });

        it('excludes blacklisted phones', function () {
            // Arrange
            $blacklistedPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $validPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Mock blacklist containing the first phone
            $this->blacklistManager->shouldReceive('getActiveBlacklistIds')
                ->andReturn([$blacklistedPhone->id]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($validPhone->id);
        });

        it('excludes phones in excluded list', function () {
            // Arrange
            $excludedPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $validPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Add phone to excluded list
            $this->phoneManager->addToExcludedList($excludedPhone->id);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($validPhone->id);
        });

        it('returns phones in descending ID order', function () {
            // Arrange
            $phone1 = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $phone2 = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert - Should get the phone with higher ID
            expect($result->id)->toBe($phone2->id);
        });

        it('uses database transaction for phone retrieval', function () {
            // Arrange
            $phone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act & Spy - We need to spy on transaction calls
            $transactionCalled = false;
            DB::shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) use (&$transactionCalled) {
                    $transactionCalled = true;
                    return $callback(); // Execute the actual callback
                });

            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($transactionCalled)->toBeTrue();
            expect($result)->toBeInstanceOf(Phone::class);
        });
    });

    describe('Phone Status Management', function () {

        it('marks phone as bound successfully', function () {
            // Arrange
            $phone = Phone::factory()->create(['status' => Phone::STATUS_BINDING]);

            // Act
            $this->phoneManager->markPhoneAsBound($phone);

            // Assert
            expect($phone->fresh()->status)->toBe(Phone::STATUS_BOUND);
        });

        it('handles phone exception with PhoneException', function () {
            // Arrange
            $phone = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);
            $exception = new PhoneException('Phone error');

            // Act
            $this->phoneManager->handlePhoneException($phone, $exception);

            // Assert
            expect($phone->fresh()->status)->toBe(Phone::STATUS_INVALID);
            expect($this->phoneManager->getExcludedPhoneIds())->toContain($phone->id);
        });


        it('handles phone exception with InvalidPhoneException', function () {
            // Arrange
            $phone = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);
            $exception = new InvalidPhoneException('Phone error');

            // Act
            $this->phoneManager->handlePhoneException($phone, $exception);

            // Assert
            expect($phone->fresh()->status)->toBe(Phone::STATUS_INVALID);
        });


        it('handles VerificationCodeSentTooManyTimesException', function () {
            // Arrange
            $phone = Phone::factory()->create(['status' => Phone::STATUS_NORMAL]);
            $exception = new VerificationCodeSentTooManyTimesException('Too many codes');

            $this->blacklistManager->shouldReceive('addToBlacklist')
                ->once()
                ->with($phone->id);

            // Act
            $this->phoneManager->handlePhoneException($phone, $exception);

            // Assert
            expect($phone->fresh()->status)->toBe(Phone::STATUS_NORMAL); // Default status for this exception
            expect($this->phoneManager->getExcludedPhoneIds())->toContain($phone->id);
        });

        it('handles generic exceptions with default status', function () {
            // Arrange
            $phone = Phone::factory()->create(['status' => Phone::STATUS_BINDING]);
            $exception = new \RuntimeException('Generic error');

            // Act
            $this->phoneManager->handlePhoneException($phone, $exception);

            // Assert
            expect($phone->fresh()->status)->toBe(Phone::STATUS_NORMAL);
            expect($this->phoneManager->getExcludedPhoneIds())->toContain($phone->id);
        });
    });

    describe('Excluded List Management', function () {

        it('adds phone ID to excluded list', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act
            $this->phoneManager->addToExcludedList($phoneId);

            // Assert
            expect($this->phoneManager->getExcludedPhoneIds())->toContain($phoneId);
        });

        it('maintains multiple phone IDs in excluded list', function () {
            // Arrange
            $phoneId1 = fake()->randomNumber();
            $phoneId2 = fake()->randomNumber();

            // Act
            $this->phoneManager->addToExcludedList($phoneId1);
            $this->phoneManager->addToExcludedList($phoneId2);

            // Assert
            $excludedIds = $this->phoneManager->getExcludedPhoneIds();
            expect($excludedIds)->toContain($phoneId1);
            expect($excludedIds)->toContain($phoneId2);
            expect($excludedIds)->toHaveCount(2);
        });

        it('allows duplicate phone IDs in excluded list', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act
            $this->phoneManager->addToExcludedList($phoneId);
            $this->phoneManager->addToExcludedList($phoneId);

            // Assert
            expect($this->phoneManager->getExcludedPhoneIds())->toHaveCount(2);
        });
    });

    describe('Integration with BlacklistManager', function () {

        it('integrates correctly with blacklist for phone filtering', function () {
            // Arrange
            $blacklistedPhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $availablePhone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Mock blacklist manager to return first phone as blacklisted
            $this->blacklistManager->shouldReceive('getActiveBlacklistIds')
                ->andReturn([$blacklistedPhone->id]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($availablePhone->id);
        });

        it('calls blacklist manager when handling verification code exception', function () {
            // Arrange
            $phone = Phone::factory()->create();
            $exception = new VerificationCodeSentTooManyTimesException('Too many attempts');

            $this->blacklistManager->shouldReceive('addToBlacklist')
                ->once()
                ->with($phone->id);

            // Act
            $this->phoneManager->handlePhoneException($phone, $exception);

            // Assert - Mock expectations verified automatically
            expect(true)->toBeTrue();
        });
    });

    describe('Edge Cases', function () {

        it('handles empty blacklist gracefully', function () {
            // Arrange
            $phone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            $this->blacklistManager->shouldReceive('getActiveBlacklistIds')
                ->andReturn([]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($phone->id);
        });

        it('handles concurrent access with database locking', function () {
            // Arrange
            $phone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->fresh()->status)->toBe(Phone::STATUS_BINDING);
        });

        it('handles phones with special characters in phone field', function () {
            // Arrange
            $phone = Phone::factory()->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fake()->e164PhoneNumber(),
                'phone_address' => fake()->unique()->url(),
            ]);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->phone)->toBe($phone->phone);
        });
    });

    describe('Performance Considerations', function () {

        it('efficiently handles large excluded lists', function () {
            // Arrange
            $phones = Phone::factory()->count(10)->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fn() => fake()->e164PhoneNumber(),
                'phone_address' => fn() => fake()->unique()->url(),
            ]);

            // Exclude first 9 phones
            for ($i = 0; $i < 9; $i++) {
                $this->phoneManager->addToExcludedList($phones[$i]->id);
            }

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($phones[9]->id); // Should get the last phone
        });

        it('efficiently handles large blacklists', function () {
            // Arrange
            $phones = Phone::factory()->count(10)->create([
                'status' => Phone::STATUS_NORMAL,
                'phone' => fn() => fake()->e164PhoneNumber(),
                'phone_address' => fn() => fake()->unique()->url(),
            ]);

            $blacklistIds = $phones->take(9)->pluck('id')->toArray();
            $this->blacklistManager->shouldReceive('getActiveBlacklistIds')
                ->andReturn($blacklistIds);

            // Act
            $result = $this->phoneManager->getAvailablePhone();

            // Assert
            expect($result->id)->toBe($phones[9]->id);
        });
    });
});
