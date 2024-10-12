<?php

namespace App\Apple\Integrations\AppleId;

use App\Apple\Integrations\AppleConnector;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class AppleIdConnector extends AppleConnector
{
    protected int $connectTimeout = 60;

    protected int $requestTimeout = 120;

    public function __construct(protected CacheInterface $cache,protected LoggerInterface $logger,protected string $token)
    {
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function persistentHeaders(): array
    {
        return ['X-Apple-ID-Session-Id','X-Apple-OAuth-Context','X-Apple-Session-Token','scnt'];

//        return ['X-Apple-ID-Session-Id','X-Apple-Auth-Attributes','scnt'];
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getClientId(): string
    {
        return $this->token;
    }

    public function resolveBaseUrl(): string
    {
        return 'https://appleid.apple.com';
    }

    protected function defaultHeaders(): array
    {
        return [
            'User-Agent'                  => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36',
            'Connection'                => 'Keep-Alive',
            'Content-Type'              => 'application/json',
            'Accept'                    => 'application/json, text/plain, */*',
            'Accept-Language'           => 'zh-CN,en;q=0.9,zh;q=0.8',
            'X-Apple-I-Request-Context' => 'ca',
            'X-Apple-I-TimeZone'        => 'Asia/Shanghai',
            'Sec-Fetch-Site'            => 'same-origin',
            'Sec-Fetch-Mode'            => 'cors',
            'Sec-Fetch-Dest'            => 'empty',
        ];
    }
}
