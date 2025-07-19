<?php

namespace App\Services;

use App\Exceptions\BrowserFingerprintException;
use Illuminate\Support\Facades\Cache;

class FingerprintAnalysisService
{
    private const SUSPICION_THRESHOLD = 100;
    private const REQUEST_VALIDITY_SECONDS = 60; // 请求有效期（秒）

    /**
     * Analyzes the fingerprint and throws an exception if it's deemed suspicious.
     * The method is now stateless and includes replay attack prevention.
     *
     * @param array $fingerprintResult
     * @throws BrowserFingerprintException
     */
    public function ensureNotSuspicious(array $fingerprintResult): void
    {
        $this->preventReplayAttack($fingerprintResult);

        if (empty($fingerprintResult['components'])) {
            throw new BrowserFingerprintException('缺少指纹组件信息，无法进行分析。');
        }

        $components = $fingerprintResult['components'];
        $visitorId = $fingerprintResult['visitorId'] ?? 'unknown';

        // 初始化局部变量用于本次分析
        $suspicionScore = 0;
        $reasons = [];

        $this->analyzeCriticalComponents($components, $suspicionScore, $reasons);
        $this->analyzeWebGL($components, $suspicionScore, $reasons);
        $this->analyzeFontCount($components, $suspicionScore, $reasons);
        $this->analyzeScreenResolution($components, $suspicionScore, $reasons);
        $this->analyzeHardwareConcurrency($components, $suspicionScore, $reasons);

        if ($suspicionScore >= self::SUSPICION_THRESHOLD) {
            throw new BrowserFingerprintException(
                "检测到可疑的浏览器指纹 (分数: {$suspicionScore})。",
                [
                    'visitorId' => $visitorId,
                    'score' => $suspicionScore,
                    'threshold' => self::SUSPICION_THRESHOLD,
                    'reasons' => $reasons
                ]
            );
        }
    }

    /**
     * Prevents replay attacks by verifying the timestamp and nonce.
     *
     * @param array $data
     * @throws BrowserFingerprintException
     */
    private function preventReplayAttack(array $data): void
    {
        // 1. 验证时间戳
        $timestamp = $data['timestamp'] ?? 0;
        $currentTimestamp = now()->valueOf(); // Get current time in milliseconds
        $timeDiffSeconds = ($currentTimestamp - $timestamp) / 1000;

        if (abs($timeDiffSeconds) > self::REQUEST_VALIDITY_SECONDS) {
            throw new BrowserFingerprintException('请求已过期。', ['timestamp_diff_seconds' => $timeDiffSeconds]);
        }

        // 2. 验证 Nonce (随机数)
        $nonce = $data['nonce'] ?? null;
        if (empty($nonce)) {
            throw new BrowserFingerprintException('缺少必要的请求标识。');
        }

        $cacheKey = 'fingerprint_nonce:' . $nonce;
        if (Cache::has($cacheKey)) {
            // 如果 nonce 已存在于缓存中，说明是重放攻击
            throw new BrowserFingerprintException('检测到重放攻击。');
        }

        // 将新的 nonce 存入缓存，有效期与请求有效期相同
        Cache::put($cacheKey, true, self::REQUEST_VALIDITY_SECONDS);
    }

    private function addSuspicion(int $score, string $reason, int &$currentScore, array &$reasons): void
    {
        $currentScore += $score;
        $reasons[] = "{$reason} (+{$score})";
    }

    private function analyzeCriticalComponents(array $components, int &$suspicionScore, array &$reasons): void
    {
        if (empty($components['canvas'])) {
            $this->addSuspicion(50, '缺少 Canvas 指纹', $suspicionScore, $reasons);
        }
        if (empty($components['webgl'])) {
            $this->addSuspicion(25, '缺少 WebGL 指纹', $suspicionScore, $reasons);
        }
        if (empty($components['audio'])) {
            $this->addSuspicion(20, '缺少 Audio 指纹', $suspicionScore, $reasons);
        }
    }

    private function analyzeWebGL(array $components, int &$suspicionScore, array &$reasons): void
    {
        if (!empty($components['webgl']['value'])) {
            $renderer = strtolower($components['webgl']['value']['renderer'] ?? '');
            if (str_contains($renderer, 'swiftshader') || str_contains($renderer, 'llvmpipe')) {
                $this->addSuspicion(60, "检测到可疑的软件渲染器 ({$renderer})", $suspicionScore, $reasons);
            }
        }
    }

    private function analyzeFontCount(array $components, int &$suspicionScore, array &$reasons): void
    {
        $fontCount = isset($components['fonts']['value']) ? count($components['fonts']['value']) : 0;
        if ($fontCount < 10) {
            $this->addSuspicion(30, "系统字体数量过少 ({$fontCount})", $suspicionScore, $reasons);
        }
    }

    private function analyzeScreenResolution(array $components, int &$suspicionScore, array &$reasons): void
    {
        if (isset($components['screenResolution']['value']) && $components['screenResolution']['value'] === [0, 0]) {
            $this->addSuspicion(50, '屏幕分辨率无效 (0x0)', $suspicionScore, $reasons);
        }
    }

    private function analyzeHardwareConcurrency(array $components, int &$suspicionScore, array &$reasons): void
    {
        $hardwareConcurrency = isset($components['hardwareConcurrency']['value']) ? $components['hardwareConcurrency']['value'] : 0;
        if ($hardwareConcurrency < 2) {
            $this->addSuspicion(20, "硬件并发数过低 ({$hardwareConcurrency})", $suspicionScore, $reasons);
        }
    }
}
