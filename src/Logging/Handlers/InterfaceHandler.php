<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\Level;
use Monolog\LogRecord;
use SytxLabs\ErrorLogger\Logging\Handlers\Traits\CorrectHandlerInterface;
use SytxLabs\ErrorLogger\Support\Config;

class InterfaceHandler implements HandlerInterface, ProcessableHandlerInterface
{
    use CorrectHandlerInterface;
    use ProcessableHandlerTrait;

    private HandlerInterface $handler;

    public function __construct(AbstractProcessingHandler $class, Level $level, Config $config)
    {
        $this->handler = $this->getCorrectHandler($class, $level, $config);
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
