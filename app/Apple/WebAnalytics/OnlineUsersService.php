<?php

namespace App\Apple\WebAnalytics;

use App\Apple\WebAnalytics\Enums\Route;
use Illuminate\Contracts\Container\Container;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * 在线用户服务类
 *
 * 该类用于跟踪和管理网站的在线用户数据。
 * 它使用 Redis 来存储和检索用户访问信息。
 */
class OnlineUsersService
{
    /**
     * @var RedisManager Redis 管理器实例
     */
    protected RedisManager $redis;

    /**
     * @var int 在线阈值（秒）
     */
    protected int $onlineThreshold = 60;

    protected ?Collection $data = null;

    /**
     * @var string Redis 键前缀
     */
    protected string $keyPrefix = 'online_users:';

    /**
     * 构造函数
     *
     * @param Container $container 容器实例，用于获取 Redis 连接
     */
    public function __construct(protected Container $container)
    {
        $this->redis = $this->container->get('redis');
        $this->data = collect();
    }

    public function getConfigPrefix()
    {
        return config('database.redis.options.prefix', '');
    }

    public function getOnlineAllPages(): Collection
    {
        $keys = $this->redis->keys($this->formatKey());
        $now = time();
        $prefix = $this->getConfigPrefix();
        foreach ($keys as $key) {

            // 移除 Laravel 添加的前缀
            $unprefixedKey = substr($key, strlen($prefix));

            $uri = $this->extractUriFromKey($unprefixedKey);

            $users = $this->redis->hGetAll($unprefixedKey);

            $activeUsersCount = $this->countActiveUsers($users, $now);
            $this->data[$uri] = $activeUsersCount;
        }

        return $this->data;
    }

    /**
     * 获取所有页面的在线用户数量
     *
     * @param int|null $limit 限制返回的页面数量
     * @return Collection 包含每个页面在线用户数的数组
     */
    public function getOnlineCountsForAllPages(?int $limit = null): Collection
    {
        $keys = $this->redis->keys($this->formatKey());
        $now = time();
        $data = collect();

        $prefix = $this->getConfigPrefix();
        foreach ($keys as $key) {

            // 移除 Laravel 添加的前缀
            $unprefixedKey = substr($key, strlen($prefix));

            $uri = $this->extractUriFromKey($unprefixedKey);

            $users = $this->redis->hGetAll($unprefixedKey);

            $activeUsersCount = $this->countActiveUsers($users, $now);
            $data[$uri] = $activeUsersCount;
        }

        $data->sort();

        return $limit !== null ? $data->slice($limit) : $data;
    }

    /**
     * 格式化 Redis 键
     *
     * @param string $uri URI
     * @return string 格式化后的键
     */
    public function formatKey(string $uri = '*'): string
    {
        return $this->keyPrefix . $uri;
    }

    /**
     * 记录用户访问
     *
     * @param string $uri 访问的 URI
     * @param string|null $sessionId 会话 ID
     * @return string 会话 ID
     */
    public function recordVisit(string $uri, ?string $sessionId = null): string
    {
        $sessionId = $sessionId ?: Str::random(40);
        $now = time();
        $key = $this->formatKey($uri);

        $this->redis->hSet($key, $sessionId, $now);
        $this->redis->expire($key, $this->onlineThreshold);

        return $sessionId;
    }

    /**
     * 获取特定页面的在线用户数量
     *
     * @param string $uri 页面 URI
     * @return int 在线用户数量
     */
    public function getOnlineCount(string $uri): int
    {
        $key = $this->formatKey($uri);
        $users = $this->redis->hGetAll($key);

        return $this->countActiveUsers($users, time());
    }

    /**
     * 获取总在线用户数量
     *
     * @return int 总在线用户数量
     */
    public function getTotalOnlineCount(): int
    {
        $keys = $this->redis->keys($this->formatKey());
        $now = time();
        $total = 0;

        foreach ($keys as $key) {
            $users = $this->redis->hGetAll($key);
            $total += $this->countActiveUsers($users, $now);
        }

        return $total;
    }

    /**
     * 从 Redis 键中提取 URI
     *
     * @param string $key Redis 键
     * @return string URI
     */
    protected function extractUriFromKey(string $key): string
    {
        $name =  str_replace($this->keyPrefix, '', $key);

        $route = Route::tryFrom($name);

        return $route ? $route->description() : $name;
    }

    /**
     * 计算活跃用户数量
     *
     * @param array $users 用户访问时间数组
     * @param int $now 当前时间戳
     * @return int 活跃用户数量
     */
    protected function countActiveUsers(array $users, int $now): int
    {
        return count(array_filter($users, fn($lastVisit) => ($now - $lastVisit) <= $this->onlineThreshold));
    }
}
