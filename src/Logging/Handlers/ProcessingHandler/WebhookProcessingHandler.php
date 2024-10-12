<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler;

use InvalidArgumentException;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Enums\WebhookFormat;

class WebhookProcessingHandler extends AbstractProcessingHandler
{
    protected string|null $url = null;
    protected string|null $secretType = null;
    protected array|string|null $secret = null;
    protected string $method = 'POST';
    private WebhookFormat $format;
    private ?string $errorMessage = null;

    public function __construct(int|Level|string $level, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $url = config('error-logger.webhook.url');
        $this->secretType = config('error-logger.webhook.secret_type');
        $this->secret = config('error-logger.webhook.secret');
        $this->method = config('error-logger.webhook.method', 'POST');
        $this->format = WebhookFormat::tryFrom(strtolower(config('error-logger.webhook.format', 'json'))) ?? WebhookFormat::Json;
        if (trim($url ?? '') !== '') {
            $this->url = $url;
        } else {
            throw new InvalidArgumentException('Webhook URL is not set.');
        }
    }

    public function close(): void
    {
        $this->url = null;
        $this->secretType = null;
        $this->secret = null;
        $this->method = 'POST';
        $this->format = WebhookFormat::Json;
        parent::close();
    }

    protected function write(LogRecord $record): void
    {
        if (trim($this->url ?? '') === '') {
            throw new LogicException('Missing webhook url, the webhook can not be opened. This may be caused by a premature call to close().' . Utils::getRecordMessageForException($record));
        }
        $this->errorMessage = null;
        set_error_handler([$this, 'customErrorHandler']);
        $this->format->send($this->url, $this->method, $this->secretType, $this->secret, $record);
        if ($this->errorMessage !== null) {
            throw new LogicException('Failed to send webhook: ' . $this->errorMessage . Utils::getRecordMessageForException($record));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function customErrorHandler(int $errno, string $errStr): bool
    {
        $this->errorMessage = $errStr;
        return true;
    }
}
