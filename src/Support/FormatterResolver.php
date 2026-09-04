<?php

namespace SytxLabs\ErrorLogger\Support;

use Monolog\Formatter\FormatterInterface;
use ReflectionClass;
use Throwable;

class FormatterResolver
{
    /** @param callable(): FormatterInterface $default */
    public static function resolve(mixed $formatter, callable $default): FormatterInterface
    {
        if ($formatter instanceof FormatterInterface) {
            return $formatter;
        }
        if (is_string($formatter) && is_a($formatter, FormatterInterface::class, true)) {
            if (function_exists('app')) {
                try {
                    $resolved = app($formatter);
                    if ($resolved instanceof FormatterInterface) {
                        return $resolved;
                    }
                } catch (Throwable) {
                }
            }
            try {
                $reflection = new ReflectionClass($formatter);
                if ($reflection->isInstantiable() && ($reflection->getConstructor()?->getNumberOfRequiredParameters() ?? 0) < 1) {
                    return new $formatter();
                }
            } catch (Throwable) {
            }
        }
        try {
            if (is_callable($formatter)) {
                $resolved = $formatter();
                return $resolved instanceof FormatterInterface ? $resolved : $default();
            }
        } catch (Throwable) {
        }
        return $default();
    }
}
