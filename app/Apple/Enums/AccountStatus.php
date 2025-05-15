<?php

namespace App\Apple\Enums;

enum AccountStatus: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAIL = 'login_fail';
    case AUTH_SUCCESS = 'auth_success';
    case AUTH_FAIL = 'auth_fail';
    case BIND_SUCCESS = 'bind_success';
    case BIND_ING = 'bind_ing';
    case BIND_FAIL = 'bind_fail';
    case THEFT_PROTECTION = 'theft_protection';

    public function description(): string
    {
        return match($this) {
            self::LOGIN_SUCCESS => '登录成功',
            self::LOGIN_FAIL => '登录失败',
            self::AUTH_SUCCESS => '授权成功',
            self::AUTH_FAIL => '授权失败',
            self::BIND_SUCCESS => '绑定成功',
            self::BIND_ING => '绑定中',
            self::BIND_FAIL => '绑定失败',
            self::THEFT_PROTECTION => '失窃设备保护',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOGIN_SUCCESS, self::AUTH_SUCCESS, self::BIND_SUCCESS => 'success',
            self::LOGIN_FAIL, self::AUTH_FAIL, self::BIND_FAIL, self::THEFT_PROTECTION => 'danger',
            self::BIND_ING => 'warning',
        };
    }

    public static function getDescriptionValuesArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->description(), self::cases())
        );
    }

    public static function getColorValuesArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->color(), self::cases())
        );
    }
}
