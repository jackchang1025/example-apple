<?php

use App\Services\BlacklistManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear Redis before each test
    Redis::flushdb();

    $this->blacklistManager = new BlacklistManager();
    $this->blacklistManager->clearBlacklist(); // 确保从干净的状态开始

    // 使用反射获取私有常量
    $reflection = new ReflectionClass(BlacklistManager::class);
    $this->blacklistKey = $reflection->getConstant('BLACKLIST_KEY');
});

afterEach(function () {
    // Clean up Redis after each test
    Redis::flushdb();
});

describe('BlacklistManager', function () {

    describe('Service Initialization', function () {

        it('can be instantiated', function () {
            expect($this->blacklistManager)->toBeInstanceOf(BlacklistManager::class);
        });

        it('starts with empty blacklist', function () {
            expect($this->blacklistManager->getActiveBlacklistIds())->toBeEmpty();
        });
    });

    describe('Blacklist Operations', function () {

        it('adds phone ID to blacklist successfully', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act
            $this->blacklistManager->addToBlacklist($phoneId);

            // Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeTrue();
            expect($this->blacklistManager->getActiveBlacklistIds())->toContain($phoneId);
        });

        it('detects when phone ID is in blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();
            $this->blacklistManager->addToBlacklist($phoneId);

            // Act & Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeTrue();
        });

        it('detects when phone ID is not in blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act & Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeFalse();
        });

        it('removes phone ID from blacklist successfully', function () {
            // Arrange
            $phoneId = fake()->randomNumber();
            $this->blacklistManager->addToBlacklist($phoneId);

            // Act
            $this->blacklistManager->removeFromBlacklist($phoneId);

            // Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeFalse();
            expect($this->blacklistManager->getActiveBlacklistIds())->not->toContain($phoneId);
        });

        it('handles multiple phone IDs in blacklist', function () {
            // Arrange
            $phoneId1 = fake()->randomNumber();
            $phoneId2 = fake()->randomNumber();
            $phoneId3 = fake()->randomNumber();

            // Act
            $this->blacklistManager->addToBlacklist($phoneId1);
            $this->blacklistManager->addToBlacklist($phoneId2);
            $this->blacklistManager->addToBlacklist($phoneId3);

            // Assert
            $activeIds = $this->blacklistManager->getActiveBlacklistIds();
            expect($activeIds)->toContain($phoneId1);
            expect($activeIds)->toContain($phoneId2);
            expect($activeIds)->toContain($phoneId3);
            expect($activeIds)->toHaveCount(3);
        });

        it('clears all blacklist entries', function () {
            // Arrange
            $phoneId1 = fake()->randomNumber();
            $phoneId2 = fake()->randomNumber();
            $this->blacklistManager->addToBlacklist($phoneId1);
            $this->blacklistManager->addToBlacklist($phoneId2);

            // Act
            $this->blacklistManager->clearBlacklist();

            // Assert
            expect($this->blacklistManager->getActiveBlacklistIds())->toBeEmpty();
            expect($this->blacklistManager->isInBlacklist($phoneId1))->toBeFalse();
            expect($this->blacklistManager->isInBlacklist($phoneId2))->toBeFalse();
        });
    });

    describe('Redis Integration', function () {

        it('stores data in Redis with correct key', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act
            $this->blacklistManager->addToBlacklist($phoneId);

            // Assert
            expect(Redis::hexists('phone_code_blacklist', $phoneId))->toBeTrue();
        });

        it('sets expiration on Redis key', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act
            $this->blacklistManager->addToBlacklist($phoneId);

            // Assert
            $ttl = Redis::ttl('phone_code_blacklist');
            expect($ttl)->toBeGreaterThan(0);
            expect($ttl)->toBeLessThanOrEqual(3600); // 1 hour
        });

        it('stores timestamp when adding to blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();
            $beforeTimestamp = now()->timestamp;

            // Act
            $this->blacklistManager->addToBlacklist($phoneId);

            // Assert
            $storedTimestamp = Redis::hget('phone_code_blacklist', $phoneId);
            expect($storedTimestamp)->toBeGreaterThanOrEqual($beforeTimestamp);
            expect($storedTimestamp)->toBeLessThanOrEqual(now()->timestamp);
        });

        it('removes from Redis when removing from blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();
            $this->blacklistManager->addToBlacklist($phoneId);

            // Act
            $this->blacklistManager->removeFromBlacklist($phoneId);

            // Assert
            expect(Redis::hexists('phone_code_blacklist', $phoneId))->toBeFalse();
        });

        it('deletes Redis key when clearing blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();
            $this->blacklistManager->addToBlacklist($phoneId);

            // Act
            $this->blacklistManager->clearBlacklist();

            // Assert
            expect(Redis::exists('phone_code_blacklist'))->toBe(0);
        });
    });

    describe('Expiration Handling', function () {

        it('automatically removes expired entries when checking blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Manually add expired entry to Redis
            $expiredTimestamp = now()->subHours(2)->timestamp; // 2 hours ago
            Redis::hset('phone_code_blacklist', $phoneId, $expiredTimestamp);

            // Act & Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeFalse();
            expect(Redis::hexists('phone_code_blacklist', $phoneId))->toBeFalse();
        });

        it('keeps non-expired entries when checking blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Add fresh entry
            $this->blacklistManager->addToBlacklist($phoneId);

            // Act & Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeTrue();
        });

        it('filters out expired entries from active blacklist IDs', function () {
            // Arrange
            $activePhoneId = fake()->randomNumber();
            $expiredPhoneId = fake()->randomNumber();

            // Add active entry
            $this->blacklistManager->addToBlacklist($activePhoneId);

            // Add expired entry manually
            $expiredTimestamp = now()->subHours(2)->timestamp;
            Redis::hset('phone_code_blacklist', $expiredPhoneId, $expiredTimestamp);

            // Act
            $activeIds = $this->blacklistManager->getActiveBlacklistIds();

            // Assert
            expect($activeIds)->toContain($activePhoneId);
            expect($activeIds)->not->toContain($expiredPhoneId);
        });

        it('handles entries exactly at expiration boundary', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Add entry that expires exactly now
            $boundaryTimestamp = now()->subSeconds(3600)->timestamp; // Exactly 1 hour ago
            Redis::hset('phone_code_blacklist', $phoneId, $boundaryTimestamp);

            // Act & Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeFalse();
        });
    });

    describe('Statistics and Monitoring', function () {

        it('provides accurate statistics for empty blacklist', function () {
            // Act
            $stats = $this->blacklistManager->getStatistics();

            // Assert
            expect($stats)->toBe([
                'total' => 0,
                'active' => 0,
                'expired' => 0,
            ]);
        });

        it('provides accurate statistics for active entries only', function () {
            // Arrange
            $phoneId1 = fake()->randomNumber();
            $phoneId2 = fake()->randomNumber();
            $this->blacklistManager->addToBlacklist($phoneId1);
            $this->blacklistManager->addToBlacklist($phoneId2);

            // Act
            $stats = $this->blacklistManager->getStatistics();

            // Assert
            expect($stats)->toBe([
                'total' => 2,
                'active' => 2,
                'expired' => 0,
            ]);
        });

        it('provides accurate statistics with mixed active and expired entries', function () {
            // Arrange
            $activePhoneId = fake()->randomNumber();
            $expiredPhoneId1 = fake()->randomNumber();
            $expiredPhoneId2 = fake()->randomNumber();

            // Add active entry
            $this->blacklistManager->addToBlacklist($activePhoneId);

            // Add expired entries manually
            $expiredTimestamp = now()->subHours(2)->timestamp;
            Redis::hset('phone_code_blacklist', $expiredPhoneId1, $expiredTimestamp);
            Redis::hset('phone_code_blacklist', $expiredPhoneId2, $expiredTimestamp);

            // Act
            $stats = $this->blacklistManager->getStatistics();

            // Assert
            expect($stats)->toBe([
                'total' => 3,
                'active' => 1,
                'expired' => 2,
            ]);
        });
    });

    describe('Edge Cases', function () {

        it('handles non-existent phone ID removal gracefully', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act & Assert - Should not throw exception
            $this->blacklistManager->removeFromBlacklist($phoneId);
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeFalse();
        });

        it('handles clearing empty blacklist gracefully', function () {
            // Act & Assert - Should not throw exception
            $this->blacklistManager->clearBlacklist();
            expect($this->blacklistManager->getActiveBlacklistIds())->toBeEmpty();
        });

        it('handles duplicate additions to blacklist', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act
            $this->blacklistManager->addToBlacklist($phoneId);
            $this->blacklistManager->addToBlacklist($phoneId); // Duplicate

            // Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeTrue();
            expect($this->blacklistManager->getActiveBlacklistIds())->toHaveCount(1);
        });

        it('handles zero and negative phone IDs', function () {
            // Arrange
            $zeroId = 0;
            $negativeId = -123;

            // Act
            $this->blacklistManager->addToBlacklist($zeroId);
            $this->blacklistManager->addToBlacklist($negativeId);

            // Assert
            expect($this->blacklistManager->isInBlacklist($zeroId))->toBeTrue();
            expect($this->blacklistManager->isInBlacklist($negativeId))->toBeTrue();
        });

        it('handles very large phone IDs', function () {
            // Arrange
            $largeId = PHP_INT_MAX;

            // Act
            $this->blacklistManager->addToBlacklist($largeId);

            // Assert
            expect($this->blacklistManager->isInBlacklist($largeId))->toBeTrue();
        });

        it('handles corrupted Redis data gracefully', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Add corrupted data to Redis
            Redis::hset('phone_code_blacklist', $phoneId, 'invalid_timestamp');

            // Act & Assert - Should handle gracefully
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeFalse();
        });
    });

    describe('Performance Considerations', function () {

        it('efficiently handles large blacklists', function () {
            // Arrange
            $phoneIds = [];
            for ($i = 0; $i < 100; $i++) {
                $phoneIds[] = 1000000 + $i;
            }

            // Act
            foreach ($phoneIds as $phoneId) {
                $this->blacklistManager->addToBlacklist($phoneId);
            }

            // Assert
            $activeIds = $this->blacklistManager->getActiveBlacklistIds();
            expect($activeIds)->toHaveCount(100);

            // Verify random samples
            $sampleIds = array_slice($phoneIds, 0, 10);
            foreach ($sampleIds as $phoneId) {
                expect($activeIds)->toContain($phoneId);
            }
        });

        it('efficiently filters expired entries from large datasets', function () {
            // Arrange
            $activeCount = 50;
            $expiredCount = 50;
            $activeTimestamp = now()->timestamp;
            $expiredTimestamp = now()->subHours(2)->timestamp;

            Redis::pipeline(function ($pipe) use ($activeCount, $expiredCount, $activeTimestamp, $expiredTimestamp) {
                // Add active entries
                for ($i = 1; $i <= $activeCount; $i++) {
                    $pipe->hset($this->blacklistKey, "active_{$i}", $activeTimestamp);
                }
                // Add expired entries
                for ($i = 1; $i <= $expiredCount; $i++) {
                    $pipe->hset($this->blacklistKey, "expired_{$i}", $expiredTimestamp);
                }
            });

            // Act
            $activeIds = $this->blacklistManager->getActiveBlacklistIds();
            $stats = $this->blacklistManager->getStatistics();

            // Assert
            expect($activeIds)->toHaveCount($activeCount);
            expect($stats['total'])->toBe($activeCount + $expiredCount);
            expect($stats['active'])->toBe($activeCount);
            expect($stats['expired'])->toBe($expiredCount);
        });
    });

    describe('Concurrent Access', function () {

        it('handles concurrent additions safely', function () {
            // Arrange
            $phoneId = fake()->randomNumber();

            // Act - Simulate concurrent additions
            $this->blacklistManager->addToBlacklist($phoneId);
            $this->blacklistManager->addToBlacklist($phoneId);

            // Assert
            expect($this->blacklistManager->isInBlacklist($phoneId))->toBeTrue();
            expect($this->blacklistManager->getActiveBlacklistIds())->toHaveCount(1);
        });

        it('handles concurrent add and remove operations', function () {
            // Arrange
            $phoneId = fake()->randomNumber();
            $this->blacklistManager->addToBlacklist($phoneId);

            // Act
            $this->blacklistManager->removeFromBlacklist($phoneId);
            $isInBlacklist = $this->blacklistManager->isInBlacklist($phoneId);

            // Assert
            expect($isInBlacklist)->toBeFalse();
        });
    });
});
