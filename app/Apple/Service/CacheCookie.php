<?php

namespace App\Apple\Service;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class CacheCookie extends CookieJar
{


    public function __construct(
        protected readonly string $clientId,
        protected readonly CacheInterface $cache,
        protected readonly LoggerInterface $logger,
        protected readonly int $cookieCacheTtl = 3600,
        protected readonly bool $storeSessionCookies = true,

    )
    {
        parent::__construct();

        $this->load();
    }

    public function load(): void{

        $cookies = $this->cache->get($this->sprintf());
        if (!empty($cookies)) {
            $cookies = json_decode($cookies, true);
            foreach ($cookies as $cookie) {
                $this->setCookie(new SetCookie($cookie));
            }
        }
        $this->logger->info("Loaded cookies: " . $this->toString());
    }
    public function reload(): void
    {
        $this->clear();
        $this->load();
    }

    public function save(?int $cookieCacheTtl = null): void
    {
        $json = [];
        /** @var SetCookie $cookie */
        foreach ($this as $cookie) {
            if ($this->storeSessionCookies) {
                $json[] = $cookie->toArray();
            }
        }

        $cookieCacheTtl ??= $this->cookieCacheTtl;

        $this->cache->set($this->sprintf(), $this->encode($json),$cookieCacheTtl);
        $this->logger->info("Saved cookies: " . $this->toString());
    }

    public function sprintf(): string
    {
        return sprintf("cookie:%s",$this->clientId);
    }

    public function updateFromResponse(ResponseInterface $response): void
    {
        $setCookieHeaders = $response->getHeaders()['Set-Cookie'] ?? [];
        foreach ($setCookieHeaders as $header) {
            $cookie = SetCookie::fromString($header);
            $this->setCookie($cookie);
        }
        $this->save();
        $this->logger->info("Updated cookies from response: " . $this->toString());
    }



    public function encode(array $cookies): string
    {
        return \json_encode($cookies);
    }
    public function decode(string $cookie): array
    {
        try {
            return \json_decode($cookie, true);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Saves cookies to session when shutting down
     */
    public function __destruct()
    {
        $this->save();
    }

    /**
     * 将 cookie 格式化为指定的字符串格式
     *
     * @return string
     */
    public function toString(): string
    {
        return implode('; ', array_map(function (SetCookie $cookie) {
            return $cookie->getName() . '=' . $cookie->getValue();
        }, $this->getIterator()->getArrayCopy()));
    }

    /**
     * 魔术方法，当对象被当作字符串使用时自动调用
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
