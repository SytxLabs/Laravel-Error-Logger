<?php

namespace SytxLabs\ErrorLogger\Logging;

use Monolog\Level;
use Psr\Log\InvalidArgumentException;
use Stringable;
use SytxLabs\ErrorLogger\Logging\Monolog\ErrorLogHandler;
use Throwable;

class LogManager extends \Illuminate\Log\LogManager
{
    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function emergency($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Emergency, $message, $context, $deduplicate);
    }

    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function alert($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Alert, $message, $context, $deduplicate);
    }

    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function critical($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Critical, $message, $context, $deduplicate);
    }

    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function error($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Error, $message, $context, $deduplicate);
    }

    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function warning($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Warning, $message, $context, $deduplicate);
    }

    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function notice($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Notice, $message, $context, $deduplicate);
    }

    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function info($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Info, $message, $context, $deduplicate);
    }

    /**
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     */
    public function debug($message, array $context = [], ?int $deduplicate = null): void
    {
        $this->log(Level::Debug, $message, $context, $deduplicate);
    }

    /**
     * Log a message with the given level.
     *
     * @param string|int|Level $level
     * @param string|Stringable $message
     * @param int|null $deduplicate in seconds, or null to use default deduplication time
     *
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = [], ?int $deduplicate = null): void
    {
        try {
            $handler = config('logging.channels.error-log.handler', ErrorLogHandler::class);
            (new $handler())->log(self::toMonologLevel($level), $message, $context, [], null, $deduplicate);
        } catch (Throwable $e) {
            tap($this->createEmergencyLogger(), function ($logger) use ($e) {
                $logger->emergency('Unable to create configured logger. Using emergency logger.', [
                    'exception' => $e,
                ]);
            })->log($level, $message, $context);
        }
    }

    public static function toMonologLevel(int|Level|string $level): Level
    {
        if ($level instanceof Level) {
            return $level;
        }

        if (is_string($level)) {
            if (is_numeric($level)) {
                $levelEnum = Level::tryFrom((int) $level);
                if ($levelEnum === null) {
                    throw new InvalidArgumentException('Level "'.$level.'" is not defined, use one of: '.implode(', ', Level::NAMES + Level::VALUES));
                }
                return $levelEnum;
            }
            $upper = strtr(substr($level, 0, 1), 'dinweca', 'DINWECA') . strtolower(substr($level, 1));
            if (defined(Level::class.'::'.$upper)) {
                return constant(Level::class . '::' . $upper);
            }
            throw new InvalidArgumentException('Level "'.$level.'" is not defined, use one of: '.implode(', ', Level::NAMES + Level::VALUES));
        }

        $levelEnum = Level::tryFrom($level);
        if ($levelEnum === null) {
            throw new InvalidArgumentException('Level "'.var_export($level, true).'" is not defined, use one of: '.implode(', ', Level::NAMES + Level::VALUES));
        }
        return $levelEnum;
    }
}
