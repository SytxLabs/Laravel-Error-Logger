<?php

namespace SytxLabs\ErrorLogger\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void emergency(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void alert(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void critical(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void error(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void warning(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void notice(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void info(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void debug(string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 * @method static void log(mixed $level, string|\Stringable $message, array $context = [], int|null $deduplicate = null)
 */
class ErrorLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'error-log';
    }
}
