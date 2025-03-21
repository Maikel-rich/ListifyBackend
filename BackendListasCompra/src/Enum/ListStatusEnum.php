<?php

namespace App\Enum;

enum ListStatusEnum: int
{
    case IN_PROCESS = 1;
    case DONE = 2;

    public static function getValues(): array
    {
        return [
            self::IN_PROCESS->value,
            self::DONE->value,
        ];
    }
}
