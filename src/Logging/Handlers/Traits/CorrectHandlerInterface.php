<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\Traits;

use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Level;
use SytxLabs\ErrorLogger\Support\Config;

trait CorrectHandlerInterface
{
    private function getCorrectHandler(HandlerInterface $handler, Level $level, Config $config): HandlerInterface
    {
        if ($config->deduplicate_enabled) {
            $handler = new DeduplicationHandler(
                $handler,
                (app()->runningUnitTests() ? __DIR__ . '/../../../../tests/deduplicate.log' : $config->deduplicate_log_path),
                $config->deduplicate_level,
                $config->deduplicate_interval,
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
