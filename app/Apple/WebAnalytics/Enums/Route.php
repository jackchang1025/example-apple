<?php

namespace App\Apple\WebAnalytics\Enums;

enum Route:string
{
    case HOME = 'home';
    case SIGNIN_PAGE = 'signin_page';
    case VERIFY_ACCOUNT = 'verify_account';
    case SMS_PAGE = 'sms_page';
    case AUTH_PAGE = 'auth_page';
    case VERIFY_SECURITY_CODE = 'verify_security_code';
    case SMS_SECURITY_CODE = 'sms_security_code';
    case SEND_SECURITY_CODE = 'send_security_code';
    case GET_PHONE = 'get_phone';
    case SEND_SMS = 'send_sms';
    case AUTH_PHONE_LIST = 'auth_phone_list';
    case RESULT_PAGE = 'result_page';

    public function friendlyName(): string
    {
        return match($this) {
            self::HOME => '首页',
            self::SIGNIN_PAGE, self::AUTH_PHONE_LIST,self::VERIFY_ACCOUNT => '验证账号',
            self::SMS_PAGE, self::AUTH_PAGE, self::VERIFY_SECURITY_CODE,
            self::SMS_SECURITY_CODE, self::SEND_SECURITY_CODE, self::GET_PHONE,
            self::SEND_SMS => '授权',
            self::RESULT_PAGE => '授权成功',
            default => $this->value,
        };
    }

    public static function getAllValues(): array
    {
        return array_map(
            static fn($route) => $route->value, self::cases()
        );
    }

    public static function getRoutes(string $name): array
    {
        return array_filter(
            self::cases(),
        static fn(self $route) => $route->friendlyName() === $name);
    }

    public static function getRouteValues(string $name): array
    {
        return array_map(
            static fn(self $route) => $route->value,
            self::getRoutes($name)
        );
    }
}
