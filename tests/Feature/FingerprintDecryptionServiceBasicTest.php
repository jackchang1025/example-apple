<?php

use App\Services\FingerprintDecryptionService;
use Tests\TestCase;

class FingerprintDecryptionServiceBasicTest extends TestCase
{
    protected FingerprintDecryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FingerprintDecryptionService();
    }

    /** @test */
    public function it_can_validate_correct_fingerprint_structure()
    {
        $validData = [
            'visitorId' => 'valid_visitor_id_123',
            'components' => [
                'canvas' => ['value' => 'test'],
                'fonts' => ['value' => ['Arial']]
            ]
        ];

        $result = $this->service->validateFingerprintStructure($validData);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_missing_visitor_id()
    {
        $invalidData = [
            'components' => ['canvas' => ['value' => 'test']]
        ];

        $result = $this->service->validateFingerprintStructure($invalidData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_missing_components()
    {
        $invalidData = [
            'visitorId' => 'test123'
        ];

        $result = $this->service->validateFingerprintStructure($invalidData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_invalid_visitor_id_type()
    {
        $invalidData = [
            'visitorId' => 123456,
            'components' => ['test' => 'data']
        ];

        $result = $this->service->validateFingerprintStructure($invalidData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_short_visitor_id()
    {
        $invalidData = [
            'visitorId' => 'short',
            'components' => ['test' => 'data']
        ];

        $result = $this->service->validateFingerprintStructure($invalidData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_non_array_components()
    {
        $invalidData = [
            'visitorId' => 'valid_visitor_id_123',
            'components' => 'not_an_array'
        ];

        $result = $this->service->validateFingerprintStructure($invalidData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_empty_components()
    {
        $invalidData = [
            'visitorId' => 'valid_visitor_id_123',
            'components' => []
        ];

        $result = $this->service->validateFingerprintStructure($invalidData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_null_for_invalid_base64_data()
    {
        $result = $this->service->decryptFingerprintData('invalid_base64_!@#');
        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_empty_string()
    {
        $result = $this->service->decryptFingerprintData('');
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_decrypt_valid_encrypted_data()
    {
        $testData = [
            'visitorId' => 'test123456789',
            'components' => [
                'canvas' => ['value' => 'test_canvas_data'],
                'fonts' => ['value' => ['Arial', 'Times New Roman']]
            ]
        ];

        // 模拟前端 CryptoJS 加密过程
        $encryptedData = $this->simulateCryptoJSEncryption($testData);
        
        $result = $this->service->decryptFingerprintData($encryptedData);

        $this->assertNotNull($result);
        $this->assertEquals('test123456789', $result['visitorId']);
        $this->assertIsArray($result['components']);
        $this->assertEquals('test_canvas_data', $result['components']['canvas']['value']);
    }

    /** @test */
    public function it_performs_roundtrip_encryption_decryption()
    {
        $originalData = [
            'visitorId' => 'roundtrip_test_123456789',
            'components' => [
                'canvas' => ['value' => 'canvas_fingerprint_value'],
                'fonts' => ['value' => ['Arial', 'Times New Roman']],
                'screenResolution' => ['value' => [1920, 1080]],
                'hardwareConcurrency' => ['value' => 8]
            ]
        ];

        // 加密
        $encryptedData = $this->simulateCryptoJSEncryption($originalData);
        $this->assertNotEmpty($encryptedData);

        // 解密
        $decryptedData = $this->service->decryptFingerprintData($encryptedData);

        // 验证
        $this->assertNotNull($decryptedData);
        $this->assertEquals($originalData, $decryptedData);
    }

    /**
     * 模拟 CryptoJS 加密过程的辅助方法
     */
    private function simulateCryptoJSEncryption($data): string
    {
        $secretKey = 'your-secret-key-2024';
        $jsonString = is_string($data) ? $data : json_encode($data);
        $salt = random_bytes(8);
        
        // 模拟 CryptoJS 的 EVP_BytesToKey
        $keyLen = 32;
        $ivLen = 16;
        $targetKeySize = $keyLen + $ivLen;
        $derivedBytes = '';
        $numberOfDerivedWords = 0;

        while ($numberOfDerivedWords < $targetKeySize) {
            $hasher = hash_init('md5');
            if ($numberOfDerivedWords > 0) {
                hash_update($hasher, substr($derivedBytes, -16));
            }
            hash_update($hasher, $secretKey);
            hash_update($hasher, $salt);
            $block = hash_final($hasher, true);
            $derivedBytes .= $block;
            $numberOfDerivedWords += 16;
        }

        $key = substr($derivedBytes, 0, $keyLen);
        $iv = substr($derivedBytes, $keyLen, $ivLen);

        // 加密数据
        $encrypted = openssl_encrypt($jsonString, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        // 模拟 CryptoJS 输出格式：Salted__ + salt + encrypted data
        $encryptedWithSalt = 'Salted__' . $salt . $encrypted;
        
        return base64_encode($encryptedWithSalt);
    }
} 