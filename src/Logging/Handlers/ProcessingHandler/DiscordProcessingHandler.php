<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler;

use InvalidArgumentException;
use LogicException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Logging\Handlers\Formatter\DiscordFormatter;
use SytxLabs\ErrorLogger\Support\Config;
use SytxLabs\ErrorLogger\Support\DiscordWebhook;
use UnexpectedValueException;

class DiscordProcessingHandler extends AbstractProcessingHandler
{
    protected string|null $url = null;
    protected ?DiscordWebhook $discordWebhook = null;
    private string|null $errorMessage = null;
    private Config $config;

    public function __construct(int|Level|string $level, Config $config, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $url = $config->discord_webhook_url ?? '';
        if (trim($url) !== '') {
            $this->url = $url;
        } else {
            throw new InvalidArgumentException('Discord Webhook URL is not set.');
        }
        $this->discordWebhook = new DiscordWebhook($this->url);
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->discordWebhook !== null) {
            $this->discordWebhook = null;
        }
        $this->url = null;
    }

    protected function write(LogRecord $record): void
    {
        if (($this->discordWebhook === null) && trim($this->url ?? '') === '') {
            throw new LogicException('Missing discord webhook url, the webhook can not be opened. This may be caused by a premature call to close().' . Utils::getRecordMessageForException($record));
        }
        $this->errorMessage = null;
        set_error_handler([$this, 'customErrorHandler']);
        $discordWebhook = $this->discordWebhook = new DiscordWebhook($this->url);
        if (($avatar = $this->config->discord_avatar_url ?? '') !== '') {
            $this->discordWebhook->setAvatar($avatar);
        }
        $this->discordWebhook->setUsername(config('app.name', 'Laravel') . ' Log');
        $this->discordWebhook->setColor(match ($record->level) {
            Level::Emergency, Level::Error, Level::Critical, Level::Alert => '#dc3545',
            Level::Warning => '#ffc107',
            Level::Notice => '#17a2b8',
            Level::Info => '#28a745',
            Level::Debug => '#6c757d',
        });
        $this->setFormatter(new DiscordFormatter('Y-m-d H:i:s', $this->discordWebhook));
        $this->getFormatter()->format($record);
        if (!$discordWebhook->sendEmbed($record->datetime->format('c'))) {
            throw new UnexpectedValueException(sprintf('The discord webhook "%s" could not be opened: '.$this->errorMessage, $this->url) . Utils::getRecordMessageForException($record));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = $msg;
        return true;
    }
}
