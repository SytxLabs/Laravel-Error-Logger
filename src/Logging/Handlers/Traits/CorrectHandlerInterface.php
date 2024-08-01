<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\Traits;

use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Level;

trait CorrectHandlerInterface
{
    private function getCorrectHandler(HandlerInterface $handler, Level $level): HandlerInterface
    {
        if (config('error-logger.deduplicate.enabled', false)) {
            $handler = new DeduplicationHandler(
                $handler,
                (app()->runningUnitTests() ? __DIR__ . '/../../../../tests/deduplicate.log' : config('error-logger.deduplicate.path', storage_path('logs/deduplicate.log'))),
                config('error-logger.deduplicate.level', 'debug'),
                config('error-logger.deduplicate.interval', 5),
            );
        }
        return new WhatFailureGroupHandler([
            new FingersCrossedHandler(
                $handler,
                new ErrorLevelActivationStrategy($level),
            ),
        ]);
    }
}
