<?php

return [

    'default' => env('PROXY_STORE', 'flow'),

    'stores' => [

        'flow' => [
            'orderId' => env('PROXY_ORDER_ID'),
            'pwd'     => env('PROXY_ORDER_PASSWORD'),
            'mode'    => 0,
            'pid'     => "-1",
            'cid'     => "-1",
            'sip'     => 0,
            'uid'     => "",
        ],

        'dynamic' => [
            'orderId'       => env('PROXY_ORDER_ID'),
            'secret'        => env('PROXY_ORDER_SECRET'),
            'type'          => 1,
            'num'           => 1,
            'pid'           => -1,
            'unbindTime'    => 60,
            'cid'           => '',
            'noDuplicate'   => 0,
            'dataType'      => 0,
            'lineSeparator' => 0,
            'singleIp'      => 0,
        ],
    ],
];
