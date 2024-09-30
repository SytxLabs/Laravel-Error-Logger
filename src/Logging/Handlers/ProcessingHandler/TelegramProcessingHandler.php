<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler;

use InvalidArgumentException;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Support\Telegram;
use UnexpectedValueException;

class TelegramProcessingHandler extends AbstractProcessingHandler
{
    protected string|null $chatId = null;
    protected string|null $apiToken = null;
    protected ?Telegram $telegram = null;
    private string|null $errorMessage = null;

    public function __construct(int|Level|string $level, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $chatId = config('error-logger.telegram.chat_id', '');
        $apiToken = config('error-logger.telegram.token', '');
        if (trim($chatId) !== '' && trim($apiToken) !== '') {
            $this->chatId = $chatId;
            $this->apiToken = $apiToken;
        } else {
            throw new InvalidArgumentException('Telegram phone number and api token must be set in the configuration file.');
        }
        $this->telegram = new Telegram($this->apiToken, $this->chatId);
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->telegram !== null) {
            $this->telegram = null;
        }
        $this->chatId = null;
        $this->apiToken = null;
        parent::close();
    }

    protected function write(LogRecord $record): void
    {
        if (($this->telegram === null) && (trim($this->chatId ?? '') === '' || trim($this->apiToken ?? '') === '')) {
            throw new LogicException('Missing telegram chat id. This may be caused by a premature call to close().' . Utils::getRecordMessageForException($record));
        }
        $this->errorMessage = null;
        set_error_handler([$this, 'customErrorHandler']);
        $telegram = $this->telegram = new Telegram($this->apiToken, $this->chatId);
        $message = config('app.name', 'Laravel') . ' Log' . PHP_EOL . $record->level->name . ' Log' . PHP_EOL
            . PHP_EOL . ($record->formatted ?? $record->context) . PHP_EOL . PHP_EOL . $record->datetime->format('Y-m-d H:i:s');
        if (!$telegram->send($message)) {
            throw new UnexpectedValueException(sprintf('Failed to send Telegram Message: %s. ', $this->errorMessage) . Utils::getRecordMessageForException($record));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = $msg;
        return true;
    }
}
