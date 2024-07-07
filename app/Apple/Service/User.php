<?php

namespace App\Apple\Service;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

class User
{
    protected Collection $user;

    const TOKEN = 'user_info';
    const COOKIE_LIFETIME = 60 * 24; // 24 hours

    public function __construct()
    {
        $this->load();
    }

    public function set(string $key, $value): void
    {
        $this->user->put($key, $value);
    }

    public function get(string $key): mixed{

        return $this->user->get($key);
    }
    protected function save(): void
    {
        Cookie::queue(self::TOKEN, $this->user->toJson(), self::COOKIE_LIFETIME);
    }

    protected function load(): void
    {
        $user       = Cookie::get(self::TOKEN);
        $this->user = $user ? collect(json_decode($user, true)) : collect([]);
    }

    protected function __destruct()
    {
        $this->save();
    }

    public function clear(): void
    {
        $this->user = collect([]);
        Cookie::queue(Cookie::forget(self::TOKEN));
    }
}
