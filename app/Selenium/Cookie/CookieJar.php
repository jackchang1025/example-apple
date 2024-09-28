<?php

namespace App\Selenium\Cookie;

use Facebook\WebDriver\Cookie;
use Illuminate\Support\Collection;

class CookieJar
{
    /**
     * @var Collection<Cookie>|null
     */
    protected ?Collection $cookies = null;

    /**
     * @param array|Collection $cookies
     */
    public function __construct(array|Collection $cookies = [])
    {
        $this->cookies = is_array($cookies) ? collect($cookies) : $cookies;
    }

    public function setCookies(Collection $cookies): void
    {
        $this->cookies = $cookies;
    }

    public function getCookies(): Collection
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     * @return Collection
     */
    public function extractCookies(array $cookies): Collection
    {
        return $this->cookies->mergeRecursive($cookies);
    }

    public function mergeCookies(array $newCookies): void
    {
        $this->cookies = $this->cookies->merge(
            collect($newCookies)->map(fn($cookie) => $cookie instanceof Cookie ? $cookie : Cookie::createFromArray(array_change_key_case($cookie)))
        );
    }


    public function createFromArray(array $cookies): ?Collection
    {
        array_map(function (array $cookie) {

            $cookie = array_change_key_case($cookie);

//            $cookie['domain'] = $this->normalizeDomain($cookie['domain']);
            $cookie['domain'] = ".".$cookie['domain'];
            $cookie['httpOnly'] = (bool)$cookie['httponly'];
            $cookie['sameSite'] = $cookie['samesite'] ?? 'Lax';

            $cookie = Cookie::createFromArray($cookie);

            $this->cookies->push($cookie);
        },$cookies);

        return $this->cookies;
    }

    private function normalizeDomain(string $domain): string
    {
        // 分割域名
        $parts = explode('.', $domain);

        // 如果域名部分少于2，直接返回原域名
        if (count($parts) < 2) {
            return $domain;
        }

        // 取最后两部分，并在前面加点
        return '.' . implode('.', array_slice($parts, -2));
    }
}
