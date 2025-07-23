<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * 黑名单管理器
 * 负责管理验证码发送过于频繁的手机号黑名单
 */
class BlacklistManager
{
    private const BLACKLIST_KEY = 'phone_code_blacklist';
    private const EXPIRE_SECONDS = 3600; // 1小时过期

    /**
     * 添加手机号到黑名单
     *
     * @param int $phoneId
     * @return void
     */
    public function addToBlacklist(int $phoneId): void
    {
        Redis::hset(self::BLACKLIST_KEY, $phoneId, now()->timestamp);
        Redis::expire(self::BLACKLIST_KEY, self::EXPIRE_SECONDS);
    }

    /**
     * 检查手机号是否在黑名单中
     *
     * @param int $phoneId
     * @return bool
     */
    public function isInBlacklist(int $phoneId): bool
    {
        $timestamp = Redis::hget(self::BLACKLIST_KEY, $phoneId);

        if (!$timestamp) {
            return false;
        }

        // 检查是否已过期
        if ($this->isExpired((int) $timestamp)) {
            $this->removeFromBlacklist($phoneId);
            return false;
        }

        return true;
    }

    /**
     * 从黑名单中移除手机号
     *
     * @param int $phoneId
     * @return void
     */
    public function removeFromBlacklist(int $phoneId): void
    {
        Redis::hdel(self::BLACKLIST_KEY, $phoneId);
    }

    /**
     * 获取当前有效的黑名单手机号ID列表
     *
     * @return array
     */
    public function getActiveBlacklistIds(): array
    {
        $blacklist = Redis::hgetall(self::BLACKLIST_KEY);

        $activeIds = array_keys(array_filter($blacklist, function ($timestamp) {
            return !$this->isExpired((int) $timestamp);
        }));

        // 转换为整数数组
        return array_map('intval', $activeIds);
    }

    /**
     * 清空所有黑名单记录
     *
     * @return void
     */
    public function clearBlacklist(): void
    {
        Redis::del(self::BLACKLIST_KEY);
    }

    /**
     * 获取黑名单统计信息
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $blacklist = Redis::hgetall(self::BLACKLIST_KEY);
        $activeCount = 0;
        $expiredCount = 0;

        foreach ($blacklist as $timestamp) {
            if ($this->isExpired((int)$timestamp)) {
                $expiredCount++;
            } else {
                $activeCount++;
            }
        }

        return [
            'total' => count($blacklist),
            'active' => $activeCount,
            'expired' => $expiredCount,
        ];
    }

    /**
     * 检查时间戳是否已过期
     *
     * @param int $timestamp
     * @return bool
     */
    private function isExpired(int $timestamp): bool
    {
        return (now()->timestamp - $timestamp) >= self::EXPIRE_SECONDS;
    }
}
