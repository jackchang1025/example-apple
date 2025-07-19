<?php

namespace Tests\Unit\Services;

use App\Exceptions\BrowserFingerprintException;
use App\Services\FingerprintAnalysisService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Helper function to create a valid fingerprint payload for testing.
 * It automatically includes a fresh timestamp and a unique nonce.
 */
function getTestFingerprint(array $components, string $visitorId = 'test-visitor'): array
{
    return [
        'visitorId' => $visitorId,
        'timestamp' => now()->valueOf(),
        'nonce' => Str::random(32),
        'components' => $components,
    ];
}

beforeEach(function () {
    $this->analysisService = new FingerprintAnalysisService();
});

test('it does not throw exception for a valid fingerprint', function () {
    $components = [
        'canvas' => ['value' => 'canvas-data'],
        'webgl' => ['value' => ['renderer' => 'Apple GPU']],
        'audio' => ['value' => 'audio-data'],
        'fonts' => ['value' => array_fill(0, 20, 'font')],
        'screenResolution' => ['value' => [1920, 1080]],
        'hardwareConcurrency' => ['value' => 8],
    ];
    $payload = getTestFingerprint($components, 'valid-user');

    $this->analysisService->ensureNotSuspicious($payload);

    // If no exception is thrown, the test passes.
    expect(true)->toBeTrue();
});

test('it throws exception for missing critical components', function () {
    $components = [
        // Missing canvas, webgl, audio -> Score: 50 + 25 + 20 = 95
        'fonts' => ['value' => []], // Low font count -> Score: 30
        // Total score is 125, which is >= 100
        'screenResolution' => ['value' => [1920, 1080]],
        'hardwareConcurrency' => ['value' => 8],
    ];
    $payload = getTestFingerprint($components, 'suspicious-user');

    $this->analysisService->ensureNotSuspicious($payload);
})->throws(BrowserFingerprintException::class);


test('it throws exception for suspicious webgl renderer', function () {
    $components = [
        'canvas' => ['value' => 'canvas-data'],
        'webgl' => ['value' => ['renderer' => 'SwiftShader']], // Suspicious -> Score: 60
        'audio' => ['value' => 'audio-data'],
        'fonts' => ['value' => array_fill(0, 20, 'font')],
        'screenResolution' => ['value' => [0, 0]], // Invalid resolution -> Score: 50
        // Total score is 110, which is >= 100
        'hardwareConcurrency' => ['value' => 8],
    ];
    $payload = getTestFingerprint($components, 'bot-user');
    $this->analysisService->ensureNotSuspicious($payload);
})->throws(BrowserFingerprintException::class);

test('it throws exception when multiple suspicious factors accumulate over threshold', function () {
    $components = [
        'canvas' => ['value' => 'canvas-data'],
        'webgl' => ['value' => ['renderer' => 'Apple GPU']],
        'audio' => ['value' => 'audio-data'],
        'fonts' => ['value' => ['Arial', 'Times New Roman']], // Too few fonts (30 points)
        'screenResolution' => ['value' => [0, 0]],           // Invalid resolution (50 points)
        'hardwareConcurrency' => ['value' => 1],              // Low concurrency (20 points)
    ];
    $payload = getTestFingerprint($components, 'very-suspicious-user');
    // Total score: 30 + 50 + 20 = 100, which should trigger the exception
    $this->analysisService->ensureNotSuspicious($payload);
})->throws(BrowserFingerprintException::class, '检测到可疑的浏览器指纹 (分数: 100)');


test('it does not throw an exception for a score just below the threshold', function () {
    $components = [
        'canvas' => ['value' => 'canvas-data'],
        'webgl' => ['value' => ['renderer' => 'Apple GPU']],
        'audio' => ['value' => 'audio-data'],
        'fonts' => ['value' => array_fill(0, 5, 'font')], // Too few fonts (30 points)
        'screenResolution' => ['value' => [800, 600]],
        'hardwareConcurrency' => ['value' => 1],          // Low concurrency (20 points)
    ];
    $payload = getTestFingerprint($components, 'borderline-user');
    // Total score: 30 + 20 = 50, which is less than 100
    $this->analysisService->ensureNotSuspicious($payload);
    expect(true)->toBeTrue();
});
