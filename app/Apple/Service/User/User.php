<?php

namespace App\Apple\Service\User;

use App\Models\Account;
use Illuminate\Support\Collection;
use Psr\SimpleCache\CacheInterface;
use Saloon\Repositories\ArrayStore;

class User extends ArrayStore
{

    public function __construct(
        protected CacheInterface $cache,
        protected string $token,
        protected int $ttl = 60 * 30
    ) {
        parent::__construct($this->load() ?? []);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }


    public function getAccount(): ?Account
    {
        return $this->get('account');
    }

    public function setAccount(Account $account): static
    {
        return $this->add('account', $account);
    }

    public function setCookie(array $value): static
    {
        return $this->add('cookie', $value);
    }

    public function getCookie(array $default = []): array
    {
        return $this->get('cookie', $default);
    }

    public function appendCookie(string $key, string|int|array $value): static
    {
        $cookie       = $this->getCookie();
        $cookie[$key] = $value;
        return $this->setCookie($cookie);
    }

    public function setHeader(array $value): static
    {
        return $this->add('header', $value);
    }

    public function appendHeader(string $key, string|int|array $value): static
    {
        $header       = $this->getHeaders();
        $header[$key] = $value;
        return $this->setHeader($header);
    }

    public function getHeaders(array $default = []): array
    {
        return $this->get('header', $default);
    }

    public function getHeader(mixed $key,mixed $default = null):mixed
    {
        return $this->getHeaders()[$key] ?? $default;
    }

    public function setConfig(Config $config): static
    {
        return $this->add('config', $config->toArray());
    }

    public function getConfig(): ?Config
    {
        $configArray = $this->get('config');
        return $configArray ? Config::fromArray($configArray) : null;
    }


    public function setPhoneInfo(mixed $value): static
    {
        return $this->add('phone_info', $value);
    }

    public function getPhoneInfo(array $default = []): mixed
    {
        return $this->get('phone_info', $default);
    }

    public function load():mixed
    {
        return $this->cache->get($this->token);
    }

    public function save(): bool
    {
        return $this->cache->set($this->token, $this->data, $this->ttl);
    }

    public function __destruct()
    {
        $this->save();
    }

    public function clear(): void
    {
        $this->data = [];
        $this->save();
    }
}
