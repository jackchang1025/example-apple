<?php

return [

    'default' => env('PROXY_STORE', 'flow'),

    'stores' => [

        'flow' => [
            //订单号
            'orderId' => env('PROXY_ORDER_ID'),
            //订单密码
            'pwd'     => env('PROXY_ORDER_PASSWORD'),
            // 模式，0-默认账密模式，1-通道模式。此变量只是为了代码上拼接2种使用方式的 password 参数
            'mode'    => 0,
            //省份id，-1表示随机
            'pid'     => "-1",
            //城市id，-1表示随机
            'cid'     => "-1",
            //是否切换IP，0表示自动切换，1表示不能切换，默认0
            'sip'     => 1,
            //自定义，dik情况下，相同的UID会尽可能采用相同的IP，可以认为是同一组会话，不填表示每次请求都随机
            'uid'     => "",
        ],

        'dynamic' => [
            //订单号
            'orderId'       => env('PROXY_ORDER_ID'),
            //订单密钥
            'secret'        => env('PROXY_ORDER_SECRET'),
            //ip协议  1表示HTTP/HTTPS
            'type'          => 1,
            //提取数量 1-200之间
            'num'           => 1,
            //省份 -1表示中国
            'pid'           => -1,
            //占用时长（单位秒）
            'unbindTime'    => 600,
            //城市id，-1表示随机
            'cid'           => '',
            //是否去重 0表示不去重 1表示24小时去重
            'noDuplicate'   => 0,
            //返回的数据格式 0表示json
            'dataType'      => 0,
            //
            'lineSeparator' => 0,
            //异常切换  0表示切换  1表示不切换
            'singleIp'      => 0,
        ],
    ],
];
