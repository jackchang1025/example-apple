<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Here you may configure the rate limiting settings for your API.
    | These values are used by the ApiRateLimiter middleware.
    | You can set the default values here, which can be overridden
    | by environment variables.
    |
    */

    'rate_limiting' => [
        'max_attempts' => env('API_RATE_LIMIT_MAX_ATTEMPTS', 30),
        'decay_minutes' => env('API_RATE_LIMIT_DECAY_MINUTES', 1),
    ],
];
