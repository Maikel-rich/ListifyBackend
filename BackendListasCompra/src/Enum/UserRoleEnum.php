<?php

namespace App\Enum;

enum UserRoleEnum: int
{
    case ADMIN = 1;
    case USER = 2;

    public static function getValues(): array
    {
        return [
            self::ADMIN->value,
            self::USER->value,
        ];
    }
}

