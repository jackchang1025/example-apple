<?php

namespace App\Apple\WebAnalytics\Enums;

enum Route:string
{
    case ROUTE_NAME_HOME = '/';
    case ROUTE_NAME_SMS = 'sms';
    case ROUTE_NAME_RESULT = 'result';
    case ROUTE_NAME_SIGNIN = 'signin';
    case ROUTE_NAME_AUTH = 'auth';
    case ROUTE_NAME_AUTH_PHONE_LIST = 'auth_phone_list';


    public function description(): string
    {
        return match($this) {
            self::ROUTE_NAME_HOME => '首页',
            self::ROUTE_NAME_SMS => '发送短信',
            self::ROUTE_NAME_RESULT => '授权成功',
            self::ROUTE_NAME_SIGNIN => '验证账号',
            self::ROUTE_NAME_AUTH => '授权',
            self::ROUTE_NAME_AUTH_PHONE_LIST => '选择号码',
        };
    }

    public static function getAllValues(): array
    {
        return array_map(fn($route) => $route->value, self::cases());
    }
}
