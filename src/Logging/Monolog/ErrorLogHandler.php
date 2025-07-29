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
use RuntimeException;
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
            $level = config('error-logger.' . $type->value . '.level', config('error-logger.level', 'debug'));
            $this->handlers[$type->value] = $type->getHandler($subject, Logger::toMonologLevel(Level::fromName($level)));
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

    public function handle(LogRecord $record, ?int $deduplicate = null): bool
    {
        if (count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }
        $result = false;
        foreach ($this->handlers as $type => $handler) {
            if ($this->isDuplicate($record, $type)) {
                return false;
            }
            if ($handler->handle($record)) {
                $this->deduplicateAdd($record, $type, $deduplicate);
                $result = true;
            }
        }
        return $result || $this->isHandling($record);
    }

    public function handleBatch(array $records): void
    {
        foreach ($this->handlers as $type => $handler) {
            $records = array_filter($records, fn ($record) => !$this->isDuplicate($record, $type));
            $handler->handleBatch($records);
            foreach ($records as $record) {
                $this->deduplicateAdd($record, $type);
            }
        }
    }

    public function close(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->close();
        }
        unset($this->handlers);
    }

    public function log($level, string|Stringable $message, array $context = [], array $extra = [], mixed $formatted = null, ?int $deduplicate = null): void
    {
        if (!is_string($message) && !($message instanceof Stringable)) {
            throw new InvalidArgumentException('Invalid message');
        }
        $this->handle(
            new LogRecord(now()->toDateTimeImmutable(), 'error-logger', $level, $message, $context, $extra, $formatted),
            $deduplicate
        );
    }

    public function deduplicateAdd(LogRecord $record, string $handler, ?int $deduplicate = null): void
    {
        if (!config('error-logger.deduplicate.enabled', false)) {
            return;
        }
        $path = config('error-logger.deduplicate.path', storage_path('logs/deduplication.log'));
        if (!file_exists($path)) {
            $dir = dirname($path);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
            touch($path);
        }
        $handle = fopen($path, 'ab');
        if ($handle === false) {
            throw new RuntimeException('Failed to open file for writing: ' . $path);
        }
        fwrite(
            $handle,
            $record->datetime->getTimestamp() . ':' .
            ($record->datetime->getTimestamp() + ($deduplicate ?? config('error-logger.deduplicate.interval', 60))) . ':' .
            $record->level->getName() . ':' .
            $handler . ':' .
            preg_replace('{[\r\n].*}', '', $record->message) . PHP_EOL
        );
        fclose($handle);
    }

    public function isDuplicate(LogRecord $record, string $handler): bool
    {
        if (!config('error-logger.deduplicate.enabled', false)) {
            return false;
        }
        $path = config('error-logger.deduplicate.path', storage_path('logs/deduplication.log'));
        if (!file_exists($path)) {
            return false;
        }
        $store = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $timestampValidity = $record->datetime->getTimestamp();
        $expectedMessage = preg_replace('{[\r\n].*}', '', $record->message);
        $yesterday = time() - 86400;

        $collect = false;

        foreach ($store as $log) {
            $logExploded = explode(':', $log, 5);
            if (count($logExploded) !== 5) {
                continue;
            }
            [$timestamp, $timestampValidTo, $level, $oldHandle, $message] = $logExploded;

            if ($message === $expectedMessage && $oldHandle === $handler && $level === $record->level->getName() && $timestampValidTo > $timestampValidity) {
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
        $handle = fopen($path, 'rwb+');
        if ($handle === false) {
            throw new RuntimeException('Failed to open file for reading and writing: ' . $path);
        }
        flock($handle, LOCK_EX);
        $validLogs = [];

        $timestampValidity = time();

        while (!feof($handle)) {
            $log = fgets($handle);
            if (is_string($log) && $log !== '') {
                $logExploded = explode(':', $log, 5);
                if (count($logExploded) > 1) {
                    [, $timestampValidTo] = $logExploded;
                    if ($timestampValidTo >= $timestampValidity) {
                        $validLogs[] = $log;
                    }
                }
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
