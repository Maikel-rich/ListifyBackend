<?php

namespace App\Enum;

enum ListStatusEnum: string
{
    case IN_PROCESS = 'in_process';
    case DONE = 'done';

    public static function getValues(): array
    {
        return [
            self::IN_PROCESS->value,
            self::DONE->value,
        ];
    }
}
