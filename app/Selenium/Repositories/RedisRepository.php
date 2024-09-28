<?php

namespace App\Selenium\Repositories;

use \Redis;

class RedisRepository implements RepositoriesInterface
{
    private const KEY_PREFIX = 'connector:session:';

    public function __construct(protected Redis $redis)
    {
    }

    public function add(string $name, string $session, int $ttl = 60 * 8 - 30): void
    {
        $key = $this->getKey($name);
        $this->redis->set($key, $session, ['EX' => $ttl]);
    }

    public function get(string $name): ?string
    {
        return $this->redis->get($this->getKey($name)) ?: null;
    }

    public function has(string $name): bool
    {
        return $this->redis->exists($this->getKey($name)) > 0;
    }

    public function remove(string $name): void
    {
        $this->redis->del($this->getKey($name));
    }

    public function getAll(): array
    {
        $keys = $this->redis->keys(self::KEY_PREFIX . '*');
        if (empty($keys)) {
            return [];
        }

        $values = $this->redis->mGet($keys);
        $result = [];
        foreach ($keys as $index => $key) {
            $name = substr($key, strlen(self::KEY_PREFIX));
            $result[$name] = $values[$index];
        }

        return $result;
    }

    public function save(): bool
    {
        // 使用这种方法，每次修改都已经保存，所以这里不需要额外的操作
        return true;
    }

    private function getKey(string $name): string
    {
        return self::KEY_PREFIX . $name;
    }
}
