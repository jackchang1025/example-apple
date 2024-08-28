<?php

namespace App\Apple\Service\User;

use App\Models\Account;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\CacheInterface;

class User
{
    protected ?Collection $user = null;

    public function __construct(
        protected CacheInterface $cache,
        protected string $token,
        protected int $ttl = 60 * 30
    ) {
        $this->load();
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function set(string $key, $value): void
    {
        $this->user->put($key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->user->get($key, $default);
    }

    public function getAccount(): Account|null
    {
        if($account = $this->get('account')){
            return Account::where('account',$account)->first();
        }
        return null;
    }

    public function gets(): Collection
    {
        return $this->user;
    }

    public function setCookie(array $value): void
    {
        $this->user->put('cookie', $value);
    }

    public function getCookie(array $default = []): array
    {
        return $this->user->get('cookie', $default);
    }

    public function getCookies(): array
    {
        return $this->user->get('cookie',[]);
    }

    public function appendCookie(string $key, string|int|array $value): void
    {
        $cookie       = $this->getCookie();
        $cookie[$key] = $value;
        $this->setCookie($cookie);
    }

    public function setHeader(array $value): void
    {
        $this->user->put('header', $value);
    }

    public function appendHeader(string $key, string|int|array $value): void
    {
        $header       = $this->getHeaders();
        $header[$key] = $value;
        $this->setHeader($header);
    }

    public function getHeaders(array $default = []): array
    {
        return $this->user->get('header', $default);
    }

    public function getHeader(mixed $key,mixed $default = null):mixed
    {
        return $this->getHeaders()[$key] ?? $default;
    }

    public function setConfig(Config $config): void
    {
        $this->user->put('config', $config->toArray());
    }

    public function getConfig(): ?Config
    {
        $configArray = $this->user->get('config');
        return $configArray ? Config::fromArray($configArray) : null;
    }

    public function getConfigOrDefault(): Config
    {
        $config = $this->getConfig();
        if ($config === null) {
            $config = $this->getDefaultConfig();
            $this->setConfig($config);
        }
        return $config;
    }

    private function getDefaultConfig(): Config
    {
        return new Config(
            apiUrl: 'https://appleid.apple.com',
            serviceKey: 'default_service_key',
            serviceUrl: 'https://appleid.apple.com',
            environment: 'production'
        );
    }

    public function setPhoneInfo(array $value): void
    {
        $this->user->put('phone_info', $value);
    }

    public function getPhoneInfo(array $default = []): array
    {
        return $this->user->get('phone_info', $default);
    }

    public function load(): void
    {
        $userData = $this->cache->get($this->token);
        $this->user = $userData ? new Collection(json_decode($userData, true)) : new Collection();

        Log::info('load user data', ['token' => $this->token,'user' => $this->user->all()]);
    }

    public function save(): bool
    {
        Log::info('save user data', ['token' => $this->token,'user' => $this->user->all()]);

        return $this->cache->set($this->token, $this->user->toJson(), $this->ttl);
    }

    public function __destruct()
    {
        $this->save();
    }

    public function clear(): void
    {
        $this->user = collect();
        $this->save();
    }
}
