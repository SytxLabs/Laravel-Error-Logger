<?php

namespace SytxLabs\ErrorLogger\Logging\Monolog;

use InvalidArgumentException;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\Log\AbstractLogger;
use Stringable;
use SytxLabs\ErrorLogger\Support\Config;

class ErrorLogHandler extends AbstractLogger implements HandlerInterface, ProcessableHandlerInterface
{
    use ProcessableHandlerTrait;

    private HandlerInterface $handler;
    private static ?Config $config;

    public function __construct()
    {
        $this->setConfig(self::$config ?? new Config());
    }

    public function setConfig(Config $config): void
    {
        self::$config = $config;
        $type = $config->type;
        if ($type === null) {
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
        $this->handler = $type
            ->getHandler($subject, config('error-logger.deduplicate', false), Logger::toMonologLevel($config->level), $config);
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

    public function log($level, string|Stringable $message, array $context = [], array $extra = [], mixed $formatted = null): void
    {
        $this->handler->handle(
            new LogRecord(now()->toDateTimeImmutable(), 'error-logger', $level, $message, $context, $extra, $formatted)
        );
    }
}
