<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

uses(RefreshDatabase::class);

/**
 * Simulates the frontend hybrid encryption process for testing purposes.
 */
function simulateHybridEncryption(string $publicKey, array $data): string
{
    $aesKey = openssl_random_pseudo_bytes(32);
    $iv = openssl_random_pseudo_bytes(16);
    $encryptedData = openssl_encrypt(json_encode($data), 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);

    /** @var \phpseclib3\Crypt\RSA\PublicKey $publicKeyResource */
    $publicKeyResource = PublicKeyLoader::load($publicKey);
    $encryptedKey = $publicKeyResource->withPadding(RSA::ENCRYPTION_PKCS1)->encrypt(
        json_encode(['key' => base64_encode($aesKey), 'iv' => base64_encode($iv)])
    );

    return json_encode(['key'  => base64_encode($encryptedKey), 'data' => base64_encode($encryptedData)]);
}

beforeEach(function () {
    // 1. 确保测试密钥存在
    $keysPath = base_path('tests/Fixtures/keys');
    if (!file_exists($keysPath . '/private.key')) {
        $this->artisan('app:generate-keys', ['--path' => 'tests/Fixtures/keys'])->assertExitCode(0);
    }

    // 2. 将测试私钥的路径设置到 config 中
    config(['services.fingerprint.private_key_path' => $keysPath . '/private.key']);

    // 3. 为测试创建必要的种子数据
    SecuritySetting::factory()->create();
});

function getValidFingerprintPayload(): string
{
    $publicKey = file_get_contents(base_path('tests/Fixtures/keys/public.key'));
    $fingerprintData = [
        'visitorId' => 'valid-user',
        'components' => [
            'canvas' => ['value' => 'canvas-data'],
            'webgl' => ['value' => ['renderer' => 'Apple GPU']],
            'audio' => ['value' => 'audio-data'],
            'fonts' => ['value' => array_fill(0, 20, 'font')],
            'screenResolution' => ['value' => [1920, 1080]],
            'hardwareConcurrency' => ['value' => 8],
        ]
    ];
    return simulateHybridEncryption($publicKey, $fingerprintData);
}

function getSuspiciousFingerprintPayload(): string
{
    $publicKey = file_get_contents(base_path('tests/Fixtures/keys/public.key'));
    $fingerprintData = [
        'visitorId' => 'suspicious-user',
        'components' => [
            'canvas' => ['value' => 'canvas-data'],
            'webgl' => ['value' => ['renderer' => 'SwiftShader']], // High score
            'screenResolution' => ['value' => [0, 0]],             // High score
        ]
    ];
    return simulateHybridEncryption($publicKey, $fingerprintData);
}

test('verify account successfully with valid fingerprint', function () {

    $payload = [
        'accountName' => 'test@apple.com',
        'password' => 'password',
        'fingerprint' => getValidFingerprintPayload(),
    ];

    $response = $this->postJson(route('verify_account'), $payload);

    expect($response->getStatusCode())->not->toBe(400)
        ->and($response->getStatusCode())->not->toBe(403);
});

test('it returns 400 if fingerprint is missing', function () {
    $payload = [
        'accountName' => 'test@apple.com',
        'password' => 'password',
    ];

    $this->postJson(route('verify_account'), $payload)
        ->assertStatus(400)
        ->assertJson([
            'message' => 'server error',
            'error_code' => 'FINGERPRINT_VALIDATION_FAILED'
        ]);
});

test('it returns 400 if fingerprint is tampered', function () {
    $payload = [
        'accountName' => 'test@apple.com',
        'password' => 'password',
        'fingerprint' => 'tampered-data',
    ];

    $this->postJson(route('verify_account'), $payload)
        ->assertStatus(400)
        ->assertJson([
            'message' => 'server error',
            'error_code' => 'FINGERPRINT_VALIDATION_FAILED'
        ]);
});

test('it returns 403 if fingerprint is suspicious', function () {
    $payload = [
        'accountName' => 'test@apple.com',
        'password' => 'password',
        'fingerprint' => getSuspiciousFingerprintPayload(),
    ];

    $this->postJson(route('verify_account'), $payload)
        ->assertStatus(403)
        ->assertJsonFragment(['error_code' => 'FINGERPRINT_VALIDATION_FAILED']);
});
