<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 默认IP请求驱动
    |--------------------------------------------------------------------------
    */
    'default'   => env('DEFAULT_IP_REQUEST', 'apiIpCc'),

    /*
    |--------------------------------------------------------------------------
    | IP请求服务商配置
    |--------------------------------------------------------------------------
    */
        'apiIpCc' => [
            
        ],

        'pconline' => [
            
        ],

        'ip138' => [
            'token' => env('IP138_TOKEN'),
        ],

        'ipdecodo' => [
            
        ],
];
