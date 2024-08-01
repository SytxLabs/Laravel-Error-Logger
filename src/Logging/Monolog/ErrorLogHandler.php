<?php

namespace SytxLabs\ErrorLogger\Logging\Monolog;

use InvalidArgumentException;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\Log\AbstractLogger;
use Stringable;
use SytxLabs\ErrorLogger\Enums\ErrorLogType;

class ErrorLogHandler extends AbstractLogger implements HandlerInterface, ProcessableHandlerInterface
{
    use ProcessableHandlerTrait;

    /**
     * @var HandlerInterface[] $handlers
     */
    private array $handlers;

    public function __construct()
    {
        $types = config('error-logger.types');
        if ($types === null) {
            throw new InvalidArgumentException('Invalid error log type');
        }
        $replacements = collect([
            'APP_NAME' => config('app.name'),
            'APP_BASEURL' => config('app.url'),
        ]);
        $subject = str_replace(
            $replacements->keys()->map(fn ($k) => '{{' . $k . '}}')->all(),
            $replacements->values()->all(),
            '{{APP_NAME}} [%datetime%] %channel%.%level_name%: %message%'
        );
        if (!is_array($types)) {
            $types = [$types];
        }
        $this->handlers = [];
        foreach ($types as $type) {
            $type = ErrorLogType::tryFrom($type);
            if ($type === null) {
                continue;
            }
            $this->handlers[] = $type->getHandler($subject, Logger::toMonologLevel(Level::fromName(config('error-logger.level'))));
        }
        if (count($this->handlers) === 0) {
            throw new InvalidArgumentException('No valid error log types');
        }
    }

    public function isHandling(LogRecord $record): bool
    {
        $handled = false;
        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($record)) {
                $handled = true;
            }
        }
        return $handled;
    }

    public function handle(LogRecord $record): bool
    {
        if (count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }
        $result = false;
        foreach ($this->handlers as $handler) {
            if ($handler->handle($record)) {
                $result = true;
            }
        }
        return $result;
    }

    public function handleBatch(array $records): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handleBatch($records);
        }
    }

    public function close(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->close();
        }
        unset($this->handlers);
    }

    public function log($level, string|Stringable $message, array $context = [], array $extra = [], mixed $formatted = null): void
    {
        $this->handler->handle(
            new LogRecord(now()->toDateTimeImmutable(), 'error-logger', $level, $message, $context, $extra, $formatted)
        );
    }
}
