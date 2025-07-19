<?php

namespace App\Services;

use App\Exceptions\DecryptionFailedException;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Throwable;

class FingerprintDecryptionService
{
    private RSA\PrivateKey $privateKey;

    public function __construct()
    {
        // 1. 使私钥路径可配置，并提供一个默认值
        $privateKeyPath = config('services.fingerprint.private_key_path', config_path('encryption/private.key'));

        if (!file_exists($privateKeyPath)) {
            // 在构造函数失败是严重错误，直接抛出异常
            throw new DecryptionFailedException("指纹解密服务的私钥文件未找到: {$privateKeyPath}");
        }
        $keyContents = file_get_contents($privateKeyPath);
        $this->privateKey = PublicKeyLoader::load($keyContents);
    }

    /**
     * Decrypts the hybrid encrypted fingerprint data.
     *
     * @param string $payloadJson The JSON string containing 'key' and 'data'.
     * @return array The decrypted fingerprint data.
     * @throws DecryptionFailedException
     */
    public function decrypt(string $payloadJson): array
    {
        try {
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($payload['key'], $payload['data'])) {
                throw new DecryptionFailedException('混合解密失败：payload 结构无效。');
            }

            $aesKeyPayload = $this->decryptAesKey($payload['key']);
            $decrypted = $this->decryptData($payload['data'], $aesKeyPayload['key'], $aesKeyPayload['iv']);

            return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
          

            throw new DecryptionFailedException('浏览器指纹解密或验证失败。', [], 400, $e);
        }
    }

    private function decryptAesKey(string $encryptedKeyB64): array
    {
        $encryptedKey = base64_decode($encryptedKeyB64);
        $decryptedJson = $this->privateKey->withPadding(RSA::ENCRYPTION_PKCS1)->decrypt($encryptedKey);

        if ($decryptedJson === false) {
            throw new DecryptionFailedException('AES 密钥解密失败。');
        }

        $keyPayload = json_decode($decryptedJson, true);
        if (!isset($keyPayload['key'], $keyPayload['iv'])) {
            throw new DecryptionFailedException('AES 密钥 payload 结构无效。');
        }

        return [
            'key' => base64_decode($keyPayload['key']),
            'iv' => base64_decode($keyPayload['iv']),
        ];
    }

    private function decryptData(string $encryptedData, string $key, string $iv): string
    {
        $decrypted = openssl_decrypt(
            $encryptedData,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );

        if ($decrypted === false) {
            throw new DecryptionFailedException('AES 指纹数据解密失败。');
        }

        return $decrypted;
    }
}
