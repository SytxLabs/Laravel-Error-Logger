<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Support\WhatsAppCallMeBot;
use UnexpectedValueException;

class WhatsappProcessingHandler extends AbstractProcessingHandler
{
    protected ?Collection $whatsAppCallMeBots = null;
    private string|null $errorMessage = null;

    public function __construct(int|Level|string $level, bool $bubble = true)
    {
        $this->whatsAppCallMeBots = new Collection();
        parent::__construct($level, $bubble);
        foreach (config('error-logger.whatsapp', []) as $whatsApp) {
            $phoneNumber = $whatsApp['phone_number'] ?? '';
            $apiToken = $whatsApp['api_token'] ?? '';
            if (trim($phoneNumber) !== '' && trim($apiToken) !== '') {
                $this->whatsAppCallMeBots->push(new WhatsAppCallMeBot($phoneNumber, $apiToken));
            } else {
                throw new InvalidArgumentException('WhatsApp CallMeBot phone number or API token is not set.');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->whatsAppCallMeBots !== null) {
            $this->whatsAppCallMeBots = null;
        }
        parent::close();
    }

    protected function write(LogRecord $record): void
    {
        if (($this->whatsAppCallMeBots === null) || $this->whatsAppCallMeBots->isEmpty()) {
            throw new LogicException('Missing discord webhook url, the webhook can not be opened. This may be caused by a premature call to close().' . Utils::getRecordMessageForException($record));
        }
        $this->errorMessage = null;
        set_error_handler([$this, 'customErrorHandler']);
        $message = config('app.name', 'Laravel') . ' Log' . PHP_EOL . $record->level->name . ' Log' . PHP_EOL
            . PHP_EOL . ($record->formatted ?? $record->context) . PHP_EOL . PHP_EOL . $record->datetime->format('Y-m-d H:i:s');
        $errors = [];
        foreach ($this->whatsAppCallMeBots as $whatsappHandler) {
            if (!$whatsappHandler->send($message)) {
                $errors[] = sprintf('Failed to send Whatsapp Message to Number: %s. Error: %s', $whatsappHandler->getNumber(), $this->errorMessage);
            }
        }
        if (!empty($errors)) {
            throw new UnexpectedValueException(implode(' ', $errors) . Utils::getRecordMessageForException($record));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = $msg;
        return true;
    }
}
