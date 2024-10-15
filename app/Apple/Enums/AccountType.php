<?php

namespace App\Apple\Enums;

enum AccountType: string
{
    case USER_SUBMITTED = 'submitted';
    case IMPORTED       = 'imported';

    public static function getDescriptionValuesArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(static fn($case) => $case->description(), self::cases())
        );
    }

    public function description(): string
    {
        return match ($this) {
            self::USER_SUBMITTED => '提交',
            self::IMPORTED => '导入',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::USER_SUBMITTED => 'success',
            self::IMPORTED => 'danger',
        };
    }
}
