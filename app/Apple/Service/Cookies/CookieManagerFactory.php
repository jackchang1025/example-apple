<?php
namespace App\Apple\Service\Cookies;

use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\SimpleCache\CacheInterface;

readonly class CookieManagerFactory
{

    public function __construct(
        protected CacheInterface $cache
    )
    {
    }

    public function create(string $clientId, int $cookieCacheTtl = 3600, bool $storeSessionCookies = true): CookieJarInterface
    {
        return app(CacheCookie::class, ['clientId' => $clientId,'cache'=>$this->cache,'cookieCacheTtl'=>$cookieCacheTtl,'storeSessionCookies'=>$storeSessionCookies]);
    }
}
