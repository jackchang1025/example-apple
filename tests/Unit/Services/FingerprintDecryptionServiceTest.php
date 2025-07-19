<?php

namespace Tests\Unit\Services;

use App\Exceptions\DecryptionFailedException;
use App\Services\FingerprintDecryptionService;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

/**
 * Simulates the frontend hybrid encryption process for testing purposes.
 */
function simulateHybridEncryption(string $publicKey, array $data): string
{
    // 1. Generate a random AES key and IV
    $aesKey = openssl_random_pseudo_bytes(32); // 256 bits
    $iv = openssl_random_pseudo_bytes(16);     // 128 bits

    // 2. Encrypt the data using AES
    $encryptedData = openssl_encrypt(
        json_encode($data),
        'aes-256-cbc',
        $aesKey,
        OPENSSL_RAW_DATA,
        $iv
    );

    // 3. Encrypt the AES key and IV using RSA public key
    $keyPayload = json_encode(['key' => base64_encode($aesKey), 'iv' => base64_encode($iv)]);

    /** @var \phpseclib3\Crypt\RSA\PublicKey $publicKeyResource */
    $publicKeyResource = PublicKeyLoader::load($publicKey);
    $encryptedKey = $publicKeyResource->withPadding(RSA::ENCRYPTION_PKCS1)->encrypt($keyPayload);

    // 4. Package the final payload
    return json_encode([
        'key'  => base64_encode($encryptedKey),
        'data' => base64_encode($encryptedData)
    ]);
}

beforeEach(function () {
    // 1. 确保测试密钥存在
    $keysPath = base_path('tests/Fixtures/keys');
    if (!file_exists($keysPath . '/private.key')) {
        $this->artisan('app:generate-keys', ['--path' => 'tests/Fixtures/keys'])->assertExitCode(0);
    }

    // 2. 将测试私钥的路径设置到 config 中
    config(['services.fingerprint.private_key_path' => $keysPath . '/private.key']);

    // 3. 实例化服务，它现在会自动使用上面的测试路径
    $this->decryptionService = $this->app->make(FingerprintDecryptionService::class);
});

test('it successfully decrypts a valid payload', function () {
    $publicKey = file_get_contents(base_path('tests/Fixtures/keys/public.key'));
    $originalData = ['visitorId' => 'test-visitor', 'components' => ['canvas' => 'data']];

    $encryptedPayload = simulateHybridEncryption($publicKey, $originalData);

    $decryptedData = $this->decryptionService->decrypt($encryptedPayload);

    expect($decryptedData)->toBe($originalData);
});

// 其他所有测试用例的断言现在应该可以正常工作
test('it throws an exception for malformed payload json', function () {
    $this->decryptionService->decrypt('not a json');
})->throws(DecryptionFailedException::class);

test('it throws an exception for payload with missing keys', function () {
    $payload = json_encode(['data' => 'some-data']);
    $this->decryptionService->decrypt($payload);
})->throws(DecryptionFailedException::class);

test('it throws an exception for tampered aes key', function () {
    $publicKey = file_get_contents(base_path('tests/Fixtures/keys/public.key'));
    $originalData = ['visitorId' => 'test-visitor'];

    $encryptedPayload = simulateHybridEncryption($publicKey, $originalData);
    $payloadArray = json_decode($encryptedPayload, true);

    $payloadArray['key'] = base64_encode(random_bytes(10));

    $this->decryptionService->decrypt(json_encode($payloadArray));
})->throws(DecryptionFailedException::class);

test('it throws an exception for tampered data', function () {
    $publicKey = file_get_contents(base_path('tests/Fixtures/keys/public.key'));
    $originalData = ['visitorId' => 'test-visitor'];

    $encryptedPayload = simulateHybridEncryption($publicKey, $originalData);
    $payloadArray = json_decode($encryptedPayload, true);

    $payloadArray['data'] = base64_encode(random_bytes(10));

    $this->decryptionService->decrypt(json_encode($payloadArray));
})->throws(DecryptionFailedException::class);
