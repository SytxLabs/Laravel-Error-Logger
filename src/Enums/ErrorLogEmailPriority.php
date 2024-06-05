<?php

namespace SytxLabs\ErrorLogger\Enums;

enum ErrorLogEmailPriority: string
{
    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';

    public function getPriority(): int
    {
        return match ($this) {
            self::High => 1,
            self::Normal => 3,
            self::Low => 5,
        };
    }
}
