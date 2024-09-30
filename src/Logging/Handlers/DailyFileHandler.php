<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers;

use Illuminate\Support\Carbon;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DailyFileHandler implements HandlerInterface, ProcessableHandlerInterface
{
    use ProcessableHandlerTrait;

    private HandlerInterface $handler;

    public function __construct(Level $level)
    {
        $name = storage_path(config('error-logger.daily_file.path', 'logs/log_{timespan}.log'));
        if (str_contains($name, '{timespan}')) {
            $days = config('error-logger.daily_file.days', 7);
            $name = str_replace(
                '{timespan}',
                $days > 1 ?
                Carbon::now()->format('Y-m-d') . '_' . Carbon::now()->addDays($days - 1)->format('Y-m-d') :
                Carbon::now()->format('Y-m-d'),
                $name
            );
        }
        $this->handler = new WhatFailureGroupHandler([
            new FingersCrossedHandler(
                new StreamHandler($name, $level),
                new ErrorLevelActivationStrategy($level),
            ),
        ]);
    }

    public function isHandling(LogRecord $record): bool
    {
        return $this->handler->isHandling($record);
    }

    public function handle(LogRecord $record): bool
    {
        if (count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }
        return $this->handler->handle($record);
    }

    public function handleBatch(array $records): void
    {
        $this->handler->handleBatch($records);
    }

    public function close(): void
    {
        $this->handler->close();
    }
}
