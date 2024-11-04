<?php

namespace App\Apple\WebAnalytics;

use Illuminate\Contracts\Container\Container;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * 在线用户服务类
 *
 * 该类负责跟踪和管理网站的在线用户数据，使用 Redis 进行数据存储和检索。
 */
class OnlineUsersService
{
    use OnlineUsersTrait;

    /**
     * @var RedisManager Redis 管理器实例
     */
    protected RedisManager $redis;

    /**
     * @var int 在线阈值（秒）
     */
    protected int $onlineThreshold = 5;

    /**
     * @var string Redis 键前缀
     */
    protected string $keyPrefix = 'online_users:';

    /**
     * @var string|null 默认配置前缀
     */
    protected ?string $defaultConfigPrefix = null;

    protected ?Collection $data = null;

    /**
     * 构造函数
     *
     * @param Container $container 容器实例，用于获取 Redis 连接
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected Container $container,protected CacheInterface $cache)
    {
        $this->redis = $this->container->get('redis');
    }

    public function setOnlineThreshold(int $onlineThreshold): void
    {
        $this->onlineThreshold = $onlineThreshold;
    }

    public function getOnlineThreshold(): int
    {
        return $this->onlineThreshold;
    }

    /**
     * 获取 laravel 配置 中 redis 配置的的前缀
     * @return string|null
     */
    public function getDefaultConfigPrefix(): ?string
    {
        return $this->defaultConfigPrefix ??= config('database.redis.options.prefix');
    }

    /**
     * 获取所有页面的在线用户数量
     *
     * @return Collection 包含每个页面在线用户数的集合
     */
    public function getOnlineAllPages(): Collection
    {
        if ($this->data){
            return $this->data;
        }

        // 获取所有键
        // 0 => "laravel_database_online_users:sms_security_code"
        //  1 => "laravel_database_online_users:get_phone"
        //  2 => "laravel_database_online_users:verify_security_code"
        $keys = $this->redis->keys($this->formatKey());

        $list = collect();

        /**
         * 遍历所有页面并获取在线用户数据
         */
        foreach ($keys as $key) {

            $data = $this->getOnlineByRoute($key);

            $list->put($this->getRouteName($key), $data);
        }

        return $this->data = $list;
    }


    /**
     * 去掉 laravel 配置 中 redis 配置的的前缀
     * @param string $name
     * @return string
     */
    protected function replaceDefaultConfigPrefix(string $name): string
    {
        return  Str::replace($this->getDefaultConfigPrefix(), '', $name);
    }

    /**
     * 去掉配置的的前缀
     * @param string $name
     * @return string
     */
    protected function replaceConfigPrefix(string $name): string
    {
        return  Str::replace($this->getKeyPrefix(), '', $name);
    }

    /**
     * 去掉 laravel 配置 中 redis 的前缀和 配置前缀
     * @param string $name
     * @return string
     */
    protected function getRouteName(string $name): string
    {
        return $this->replaceConfigPrefix($this->replaceDefaultConfigPrefix($name));
    }


    /**
     * 获取某个页面在线用户数据
     * @param string $name
     * @return Collection
     */
    protected function getOnlineByRoute(string $name): Collection
    {
        /**
         * 这里需要去掉 laravel 配置 中 redis 配置的的前缀 因为在使用 redis 客户端的时候 laravel 或默认添加前缀
         */
        $key = $this->replaceDefaultConfigPrefix($name);

        /**
         * 获取路由所有的用户数据
         */
        $data = $this->redis->hGetAll($key);

        /**
         * 过滤掉过期只保留在线用户的数据,返回一个集合
         */
        return $this->countActiveUsers($data, now()->timestamp);
    }

    /**
     * 获取特定路由的在线用户
     *
     * @param string $name
     * @return Collection 在线用户数量
     */
    public function getOnlineForRoute(string $name): Collection
    {
        $key = $this->formatKey($name);

        /**
         * 获取路由所有的用户数据
         */
        $data = $this->redis->hGetAll($key);

        /**
         * 过滤掉过期只保留在线用户的数据,返回一个集合
         */
        return $this->countActiveUsers($data, now()->timestamp);
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
        $key = $this->formatKey($uri);

        $this->redis->hSet($key, $sessionId, time());
        $this->redis->expire($key, $this->onlineThreshold);

        return $sessionId;
    }

    /**
     * 格式化 Redis 键
     *
     * @param string $uri URI
     * @return string 格式化后的键
     */
    public function formatKey(string $uri = '*'): string
    {
        return sprintf('%s%s', $this->keyPrefix, $uri);
    }

    public function setKeyPrefix(string $keyPrefix): void
    {
        $this->keyPrefix = $keyPrefix;
    }

    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     *
     *
     * @param array $users 用户访问时间数组
     * @param int $now 当前时间戳
     * @return Collection 活跃用户数量
     */
    protected function countActiveUsers(array $users, int $now): Collection
    {
        return collect($users)->filter(fn(int $lastVisit) => ($now - $lastVisit) <= $this->onlineThreshold);
    }
}
