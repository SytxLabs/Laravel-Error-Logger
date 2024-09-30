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
            if (!($type instanceof ErrorLogType)) {
                $type = ErrorLogType::tryFrom($type);
            }
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
        if ($this->isDuplicate($record)) {
            return false;
        }
        if (count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }
        $result = false;
        foreach ($this->handlers as $handler) {
            if ($handler->handle($record)) {
                $result = true;
            }
        }
        $result = $result || $this->isHandling($record);
        if ($result) {
            $this->deduplicateAdd($record);
        }
        return $result;
    }

    public function handleBatch(array $records): void
    {
        $records = array_filter($records, fn ($record) => !$this->isDuplicate($record));
        foreach ($this->handlers as $handler) {
            $handler->handleBatch($records);
        }
        foreach ($records as $record) {
            $this->deduplicateAdd($record);
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
        if (!is_string($message) && !($message instanceof Stringable)) {
            throw new InvalidArgumentException('Invalid message');
        }
        foreach ($this->handlers as $handler) {
            $handler->handle(
                new LogRecord(now()->toDateTimeImmutable(), 'error-logger', $level, $message, $context, $extra, $formatted)
            );
        }
    }

    public function deduplicateAdd(LogRecord $record): void
    {
        if (!config('error-logger.deduplicate.enabled', false)) {
            return;
        }
        $path = config('error-logger.deduplicate.path', storage_path('logs/deduplication.log'));
        if (!file_exists($path)) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            touch($path);
        }
        $handle = fopen($path, 'a');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open file for writing: ' . $path);
        }
        fwrite($handle, $record->datetime->getTimestamp() . ':' . $record->level->getName() . ':' . preg_replace('{[\r\n].*}', '', $record->message) . PHP_EOL);
        fclose($handle);
    }

    public function isDuplicate(LogRecord $record): bool
    {
        if (!config('error-logger.deduplicate.enabled', false)) {
            return false;
        }
        $path = config('error-logger.deduplicate.path', storage_path('logs/deduplication.log'));
        if (!file_exists($path)) {
            return false;
        }
        $store = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $timestampValidity = $record->datetime->getTimestamp() - config('error-logger.deduplicate.interval', 60);
        $expectedMessage = preg_replace('{[\r\n].*}', '', $record->message);
        $yesterday = time() - 86400;

        $collect = false;

        foreach ($store as $log) {
            [$timestamp, $level, $message] = explode(':', $log, 3);

            if ($level === $record->level->getName() && $message === $expectedMessage && $timestamp > $timestampValidity) {
                return true;
            }

            if ($timestamp < $yesterday) {
                $collect = true;
            }
        }
        if ($collect) {
            $this->deduplicateCollect();
        }
        return false;
    }

    public function deduplicateCollect(): void
    {
        if (!config('error-logger.deduplicate.enabled', false)) {
            return;
        }
        $path = config('error-logger.deduplicate.path', storage_path('logs/deduplication.log'));
        if (!file_exists($path)) {
            return;
        }
        $handle = fopen($path, 'rw+');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open file for reading and writing: ' . $path);
        }
        flock($handle, LOCK_EX);
        $validLogs = [];

        $timestampValidity = time() - config('error-logger.deduplicate.interval', 60);

        while (!feof($handle)) {
            $log = fgets($handle);
            if (is_string($log) && $log !== '' && substr($log, 0, 10) >= $timestampValidity) {
                $validLogs[] = $log;
            }
        }

        ftruncate($handle, 0);
        rewind($handle);
        foreach ($validLogs as $log) {
            fwrite($handle, $log);
        }
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
