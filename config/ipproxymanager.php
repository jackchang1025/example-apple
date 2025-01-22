<?php

use Modules\IpProxyManager\Service\HuaSheng\Dto\ExtractDto;
use Modules\IpProxyManager\Service\HuaSheng\HuaShengConnector;
use Modules\IpProxyManager\Service\HuaSheng\Requests\ExtractRequest;
use Modules\IpProxyManager\Service\Smartdaili\DTO\ProxyDto;
use Modules\IpProxyManager\Service\Smartdaili\Request\ProxyRequest;
use Modules\IpProxyManager\Service\Smartdaili\SmartdailiConnector;
use Modules\IpProxyManager\Service\Stormproxies\StormConnector;
use Modules\IpProxyManager\Service\Wandou\DTO\AccountPasswordDto;
use Modules\IpProxyManager\Service\Wandou\DTO\DynamicDto;
use Modules\IpProxyManager\Service\Wandou\Request\AccountPasswordRequest;
use Modules\IpProxyManager\Service\Wandou\Request\DynamicRequest;
use Modules\IpProxyManager\Service\Wandou\WandouConnector;

return [

    'name'      => 'IpProxyManager',

    /*
    |--------------------------------------------------------------------------
    | 默认代理驱动
    |--------------------------------------------------------------------------
    */
    'default'   => env('PROXY_DRIVER', 'huashengdaili'),

    /*
    |--------------------------------------------------------------------------
    | 代理服务商配置
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'huashengdaili' => [
            'driver'    => 'huashengdaili',
            'connector' => HuaShengConnector::class,
            'mode'      => [
                'api' => [
                    'request'        => ExtractRequest::class,
                    'dto'            => ExtractDto::class,
                    'default_config' => [
                        'time'      => 10,      // 提取的IP时长（分钟）
                        'count'     => 1,      // 提取的IP数量
                        'only'      => 0,       // 是否去重（1=去重，0=不去重）
                        'province'  => '',   // 省份编号
                        'city'      => '',      // 城市编号
                        'iptype'    => 'tunnel', // IP类型（tunnel=隧道，direct=直连）
                        'pw'        => 'no',      // 是否需要账号密码
                        'protocol'  => 'HTTP', // IP协议
                        'separator' => 1,   // 分隔符样式
                        'type'      => 'json',  // 返回类型
                        'format'    => 'city,time', // 其他返回信息
                    ],
                ],
            ],
        ],

        'stormproxies' => [
            'driver'    => 'stormproxies',
            'connector' => StormConnector::class,
            'mode'      => [
                'flow'    => [
                    'request'        => \Modules\IpProxyManager\Service\Stormproxies\Request\AccountPasswordRequest::class,
                    'dto'            => \Modules\IpProxyManager\Service\Stormproxies\DTO\AccountPasswordDto::class,
                    'default_config' => [
                        'session' => null,
                        'life'    => 1,
                        'area'    => null,
                        'city'    => null,
                        'state'   => null,
                        'ip'      => null,
                    ],
                ],
                'dynamic' => [
                    'request'        => \Modules\IpProxyManager\Service\Stormproxies\Request\DynamicRequest::class,
                    'dto'            => \Modules\IpProxyManager\Service\Stormproxies\DTO\DynamicDto::class,
                    'default_config' => [
                        'ep'       => 'hk',
                        'app_key'  => null,
                        'cc'       => 'cn',
                        'num'      => 1,
                        'city'     => null,
                        'state'    => null,
                        'life'     => 1,
                        'protocol' => 'http',
                        'format'   => 2,
                        'lb'       => 1,
                    ],
                ],
            ],
        ],

        'wandou' => [
            'driver'    => 'wandou',
            'connector' => WandouConnector::class,
            'mode'      => [
                'flow'    => [
                    'request'        => AccountPasswordRequest::class,
                    'dto'            => AccountPasswordDto::class,
                    'default_config' => [
                        'username' => null,
                        'password' => null,
                        'life' => 1,          // 尽可能保持一个ip的使用时间
                        'isp'      => null,          // 运营商
                        'pid'      => 0,       // 省份id
                        'cid'      => null,       // 城市id
                        'host'     => 'gw.wandouapp.com',           // 代理的地址
                        'port'     => '1000',           // 代理的地址
                    ],
                ],
                'dynamic' => [
                    'request'        => DynamicRequest::class,
                    'dto'            => DynamicDto::class,
                    'default_config' => [
                        'app_key' => null,    // 必需,开放的app_key
                        'num'     => 1,           // 可选,单次提取IP数量
                        'xy'      => 1,            // 可选,代理协议 1.http 3.socks
                        'type'    => 2,          // 可选,返回数据格式 1.txt 2.json
                        'lb'      => 1,            // 可选,分割符
                        'nr'      => 0,            // 可选,自动去重
                        'area_id' => 0,       // 可选,地区id
                        'isp'     => 0,           // 可选,运营商
                    ],
                ],
            ],
        ],
        'iproyal' => [
            'connector' => \Modules\IpProxyManager\Service\IpRoyal\IpRoyalConnector::class,
            'mode'      => [
                'residential' => [
                    'dto'            => \Modules\IpProxyManager\Service\IpRoyal\DTO\ProxyDto::class,
                    'request'        => \Modules\IpProxyManager\Service\IpRoyal\Request\ProxyRequest::class,
                    'default_config' => [
                        'protocol'         => 'http',
                        'sticky_session'   => false,
                        'session_duration' => 10,
                        'streaming'        => false,
                        'skip_isp_static'  => false,
                        'skip_ips_list'    => null,
                        'endpoint'         => 'geo.iproyal.com',
                    ],
                ],
                'datacenter'  => [
                    'dto'            => \Modules\IpProxyManager\Service\IpRoyal\DTO\ProxyDto::class,
                    'request'        => \Modules\IpProxyManager\Service\IpRoyal\Request\ProxyRequest::class,
                    'default_config' => [
                        'protocol'         => 'http',
                        'sticky_session'   => false,
                        'session_duration' => 10,
                        'endpoint'         => 'dc.iproyal.com',
                        'port'             => 12321,
                    ],
                ],
                'mobile'      => [
                    'dto'            => \Modules\IpProxyManager\Service\IpRoyal\DTO\ProxyDto::class,
                    'request'        => \Modules\IpProxyManager\Service\IpRoyal\Request\ProxyRequest::class,
                    'default_config' => [
                        'protocol'         => 'http',
                        'sticky_session'   => false,
                        'session_duration' => 10,
                        'endpoint'         => 'mobile.iproyal.com',
                        'port'             => 12321,
                    ],
                ],
            ],
        ],
        'smartdaili' => [
            'driver'    => 'smartdaili',
            'connector' => SmartdailiConnector::class,
            'mode'      => [
                'flow' => [
                    'request'        => ProxyRequest::class,
                    'dto'            => ProxyDto::class,
                    'default_config' => [

                    ],
                ],
            ],
        ],
    ],
];
