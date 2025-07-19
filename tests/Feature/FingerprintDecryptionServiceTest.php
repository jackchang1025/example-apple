<?php

use App\Services\FingerprintDecryptionService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->service = new FingerprintDecryptionService();
});

describe('FingerprintDecryptionService', function () {

    describe('decryptFingerprintData method', function () {

        it('成功解密有效的加密数据', function () {
            // 创建测试数据
            $testData = [
                'visitorId' => 'test123456789',
                'components' => [
                    'canvas' => ['value' => 'test_canvas_data'],
                    'webgl' => ['value' => 'test_webgl_data'],
                    'fonts' => ['value' => ['Arial', 'Times New Roman']],
                    'audio' => ['value' => 'test_audio_data'],
                    'screenResolution' => ['value' => [1920, 1080]],
                    'hardwareConcurrency' => ['value' => 8]
                ]
            ];

            // 模拟前端 CryptoJS 加密过程
            $encryptedData = simulateCryptoJSEncryption($testData);

            $result = $this->service->decryptFingerprintData($encryptedData);

            expect($result)->not->toBeNull()
                ->and($result['visitorId'])->toBe('test123456789')
                ->and($result['components'])->toBeArray()
                ->and($result['components']['canvas']['value'])->toBe('test_canvas_data');
        });

        it('处理无效的 Base64 数据时返回 null', function () {
            $result = $this->service->decryptFingerprintData('invalid_base64_!@#');
            expect($result)->toBeNull();
        });

        it('处理缺少 Salted__ 前缀的数据时返回 null', function () {
            $invalidData = base64_encode('Invalid__test_data_without_proper_format');
            $result = $this->service->decryptFingerprintData($invalidData);
            expect($result)->toBeNull();
        });

        it('处理解密失败时返回 null', function () {
            // 创建一个格式正确但密钥错误的加密数据
            $fakeData = 'Salted__' . random_bytes(8) . random_bytes(32);
            $encryptedData = base64_encode($fakeData);

            $result = $this->service->decryptFingerprintData($encryptedData);
            expect($result)->toBeNull();
        });

        it('处理无效 JSON 数据时返回 null', function () {
            // 创建一个能解密但不是有效 JSON 的数据
            $invalidJsonData = simulateCryptoJSEncryption('invalid json data {{{');
            $result = $this->service->decryptFingerprintData($invalidJsonData);
            expect($result)->toBeNull();
        });

        it('处理空字符串时返回 null', function () {
            $result = $this->service->decryptFingerprintData('');
            expect($result)->toBeNull();
        });

        it('处理异常情况时返回 null', function () {
            // 传递空字符串会触发异常处理逻辑
            $result = $this->service->decryptFingerprintData('');
            expect($result)->toBeNull();
        });
    });

    describe('validateFingerprintStructure method', function () {

        it('验证正确的指纹结构返回 true', function () {
            $validData = [
                'visitorId' => 'valid_visitor_id_123',
                'components' => [
                    'canvas' => ['value' => 'test'],
                    'fonts' => ['value' => ['Arial']]
                ]
            ];

            $result = $this->service->validateFingerprintStructure($validData);

            expect($result)->toBeTrue();
        });

        it('缺少 visitorId 时返回 false', function () {
            $invalidData = [
                'components' => ['canvas' => ['value' => 'test']]
            ];

            $result = $this->service->validateFingerprintStructure($invalidData);
            expect($result)->toBeFalse();
        });

        it('缺少 components 时返回 false', function () {
            $invalidData = [
                'visitorId' => 'test123'
            ];

            $result = $this->service->validateFingerprintStructure($invalidData);
            expect($result)->toBeFalse();
        });

        it('visitorId 不是字符串时返回 false', function () {
            $invalidData = [
                'visitorId' => 123456,
                'components' => ['test' => 'data']
            ];

            $result = $this->service->validateFingerprintStructure($invalidData);
            expect($result)->toBeFalse();
        });

        it('visitorId 长度不足时返回 false', function () {
            $invalidData = [
                'visitorId' => 'short',
                'components' => ['test' => 'data']
            ];

            $result = $this->service->validateFingerprintStructure($invalidData);
            expect($result)->toBeFalse();
        });

        it('components 不是数组时返回 false', function () {
            $invalidData = [
                'visitorId' => 'valid_visitor_id_123',
                'components' => 'not_an_array'
            ];

            $result = $this->service->validateFingerprintStructure($invalidData);
            expect($result)->toBeFalse();
        });

        it('components 是空数组时返回 false', function () {
            $invalidData = [
                'visitorId' => 'valid_visitor_id_123',
                'components' => []
            ];

            $result = $this->service->validateFingerprintStructure($invalidData);
            expect($result)->toBeFalse();
        });
    });

    describe('加密解密往返测试', function () {

        it('加密后解密应该返回原始数据', function () {
            $originalData = [
                'visitorId' => 'roundtrip_test_123456789',
                'components' => [
                    'canvas' => ['value' => 'canvas_fingerprint_value'],
                    'webgl' => ['value' => 'webgl_fingerprint_value'],
                    'fonts' => ['value' => ['Arial', 'Times New Roman', 'Helvetica']],
                    'audio' => ['value' => 'audio_fingerprint_value'],
                    'screenResolution' => ['value' => [1920, 1080]],
                    'hardwareConcurrency' => ['value' => 8],
                    'timezone' => ['value' => 'Asia/Shanghai']
                ]
            ];

            // 加密
            $encryptedData = simulateCryptoJSEncryption($originalData);
            expect($encryptedData)->not->toBeEmpty();

            // 解密
            $decryptedData = $this->service->decryptFingerprintData($encryptedData);

            // 验证
            expect($decryptedData)->not->toBeNull()
                ->and($decryptedData)->toBe($originalData);
        });

        it('多次加密同样数据应该产生不同的密文', function () {
            $data = ['visitorId' => 'test123', 'components' => ['test' => 'data']];

            $encrypted1 = simulateCryptoJSEncryption($data);
            $encrypted2 = simulateCryptoJSEncryption($data);

            expect($encrypted1)->not->toBe($encrypted2);

            // 但解密结果应该相同
            $decrypted1 = $this->service->decryptFingerprintData($encrypted1);
            $decrypted2 = $this->service->decryptFingerprintData($encrypted2);

            expect($decrypted1)->toBe($decrypted2);
        });
    });

    describe('边界条件测试', function () {

        it('处理极大的数据结构', function () {
            $largeComponents = [];
            for ($i = 0; $i < 100; $i++) {
                $largeComponents["component_$i"] = ['value' => str_repeat('data', 100)];
            }

            $largeData = [
                'visitorId' => 'large_data_test_123',
                'components' => $largeComponents
            ];

            $encryptedData = simulateCryptoJSEncryption($largeData);
            $decryptedData = $this->service->decryptFingerprintData($encryptedData);

            expect($decryptedData)->not->toBeNull()
                ->and(count($decryptedData['components']))->toBe(100);
        });

        it('处理空的组件值', function () {
            $dataWithEmptyComponents = [
                'visitorId' => 'empty_components_test',
                'components' => [
                    'empty_canvas' => ['value' => ''],
                    'null_fonts' => ['value' => null],
                    'zero_concurrency' => ['value' => 0]
                ]
            ];

            $encryptedData = simulateCryptoJSEncryption($dataWithEmptyComponents);
            $decryptedData = $this->service->decryptFingerprintData($encryptedData);

            expect($decryptedData)->not->toBeNull()
                ->and($decryptedData['components']['empty_canvas']['value'])->toBe('')
                ->and($decryptedData['components']['null_fonts']['value'])->toBeNull()
                ->and($decryptedData['components']['zero_concurrency']['value'])->toBe(0);
        });

        it('处理特殊字符和 Unicode', function () {
            $unicodeData = [
                'visitorId' => 'unicode_test_🔒🌍',
                'components' => [
                    'chinese' => ['value' => '中文测试数据'],
                    'emoji' => ['value' => '🚀🔐💻'],
                    'special_chars' => ['value' => '!@#$%^&*()_+-=[]{}|;:,.<>?']
                ]
            ];

            $encryptedData = simulateCryptoJSEncryption($unicodeData);
            $decryptedData = $this->service->decryptFingerprintData($encryptedData);

            expect($decryptedData)->not->toBeNull()
                ->and($decryptedData['visitorId'])->toBe('unicode_test_🔒🌍')
                ->and($decryptedData['components']['chinese']['value'])->toBe('中文测试数据');
        });
    });

    describe('性能测试', function () {

        it('批量解密性能测试', function () {
            $testData = [
                'visitorId' => 'performance_test_123',
                'components' => [
                    'canvas' => ['value' => 'test_data'],
                    'fonts' => ['value' => ['Arial', 'Times']]
                ]
            ];

            $encryptedData = simulateCryptoJSEncryption($testData);

            $startTime = microtime(true);

            // 解密 100 次
            for ($i = 0; $i < 100; $i++) {
                $result = $this->service->decryptFingerprintData($encryptedData);
                expect($result)->not->toBeNull();
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // 100 次解密应该在 1 秒内完成
            expect($executionTime)->toBeLessThan(1.0);
        });
    });
});

// 辅助方法：模拟 CryptoJS 加密过程
function simulateCryptoJSEncryption($data): string
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
        if (!empty($derivedBytes)) {
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
 