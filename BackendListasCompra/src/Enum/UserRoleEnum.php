<?php

namespace App\Enum;

enum UserRoleEnum: string
{
    case ADMIN = 'admin';
    case USER = 'user';

    public static function getValues(): array
    {
        return [
            self::ADMIN->value,
            self::USER->value,
        ];
    }
}
