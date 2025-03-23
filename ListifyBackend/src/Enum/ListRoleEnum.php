<?php

namespace App\Enum;

enum ListRoleEnum: int
{
    case READER = 1;
    case OWNER = 2;

    public static function getValues(): array
    {
        return [
            self::READER->value,
            self::OWNER->value,
        ];
    }
}

