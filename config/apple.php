<?php

return [

    'apple_auth' => [
        'url' => env('APPLE_AUTH', '127.0.0.1:8080'),
    ],

    'headers'    => [
        'Accept-Language' => 'zh-cn',
    ],

    // Cache settings
    'cache'      => [
        'ttl'    => env('APPLE_CACHE_TTL', 3600),
        'prefix' => [
            'header'       => 'header',
            'authenticate' => 'authenticate',
        ],
    ],

    // Cookie settings
    'cookie'     => [
        'class' => \Modules\AppleClient\Service\Cookies\Cookies::class,
        'ttl'   => env('APPLE_COOKIE_TTL', 3600),
    ],

    // Header synchronization settings
    'header'     => [
        'class' => \Modules\AppleClient\Service\Header\HeaderSynchronize::class,
        'store' => [
            'class'       => \Modules\AppleClient\Service\Store\CacheStore::class,
            'ttl'         => env('APPLE_HEADER_TTL', 3600),
            'prefix'      => 'header',
            'defaultData' => [],
        ],
    ],

    // Logger settings
    'logger'     => [
        'class' => \Psr\Log\LoggerInterface::class,
    ],

    // Proxy settings
    'proxy_service' => [
        'class' => \Modules\IpProxyManager\Service\ProxyService::class,
    ],

    // Retry settings
    'retry'      => [
        'handler'               => \Modules\AppleClient\Service\Retry\DefaultRetryHandler::class,
        'tries'                 => env('APPLE_RETRY_ATTEMPTS', 3),
        'retryInterval'         => env('APPLE_RETRY_SLEEP', 1000), // milliseconds
        'useExponentialBackoff' => env('APPLE_RETRY_USE_EXPONENTIAL_BACKOFF', true),
    ],

    // Debug mode
    'debug'      => env('APPLE_DEBUG', false),

    // Middleware settings
    'middleware' => [
        'authenticate' => \Modules\AppleClient\Service\Middleware\AuthenticateMiddleware::class,
        'debug'        => \Modules\AppleClient\Service\Middleware\DebugMiddleware::class,
    ],
    'apple_auth_url' => env('APPLE_AUTH_URL')
];
