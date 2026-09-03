<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Logging\Handlers\Formatter\IssueFormatter;
use SytxLabs\ErrorLogger\Support\FormatterResolver;
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
        $config = config('error-logger.whatsapp', []);
        foreach ($config as $key => $whatsApp) {
            if (!is_array($whatsApp) || in_array($key, ['level', 'deduplicate', 'formatter'], true)) {
                continue;
            }
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
        $this->setFormatter(FormatterResolver::resolve(config('error-logger.whatsapp.formatter'), static fn () => new IssueFormatter('d.m.Y H:i:s T')));
        $message = config('app.name', 'Laravel') . ' Log' . PHP_EOL . $record->level->name . ' Log' . PHP_EOL
            . PHP_EOL . $this->getFormatter()->format($record) . PHP_EOL . PHP_EOL . $record->datetime->format('Y-m-d H:i:s');
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
    public function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = $msg;
        return true;
    }
}
