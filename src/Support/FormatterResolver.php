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
                $constructor = $reflection->getConstructor();
                if (!$reflection->isInstantiable() || ($constructor?->getNumberOfRequiredParameters() ?? 0) > 0) {
                    return $default();
                }
                return new $formatter();
            } catch (Throwable) {
            }
            return $default();
        }
        if (!is_string($formatter) && is_callable($formatter)) {
            try {
                $resolved = $formatter();
            } catch (Throwable) {
                return $default();
            }
            return $resolved instanceof FormatterInterface ? $resolved : $default();
        }
        return $default();
    }
}
