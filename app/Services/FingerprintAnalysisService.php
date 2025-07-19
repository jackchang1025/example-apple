<?php

namespace App\Services;

use App\Exceptions\BrowserFingerprintException;

class FingerprintAnalysisService
{
    private const SUSPICION_THRESHOLD = 100;

    /**
     * Analyzes the fingerprint and throws an exception if it's deemed suspicious.
     * The method is now stateless.
     *
     * @param array $fingerprintResult
     * @throws BrowserFingerprintException
     */
    public function ensureNotSuspicious(array $fingerprintResult): void
    {
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
