<?php

namespace SytxLabs\ErrorLogger\Support;

use Monolog\Level;
use SytxLabs\ErrorLogger\Enums\ErrorLogEmailPriority;
use SytxLabs\ErrorLogger\Enums\ErrorLogType;

class Config
{
    public ?ErrorLogType $type;
    public bool $deduplicate;
    public Level $level;

    public string $file_path;

    public string $daily_file_path;
    public int $daily_file_days;

    public string $email_default_subject;
    public array $email_to;
    public array $email_from;
    public array $email_reply_to;
    public ErrorLogEmailPriority $email_priority;

    public string $discord_webhook_url;
    public string $discord_username;
    public string $discord_avatar_url;

    public array $whatsapp;

    public string $github_url;
    public string $github_token;

    public string $gitlab_url;
    public string $gitlab_token;

    public string $telegram_token;
    public string $telegram_chat_id;

    public function __construct()
    {
        $this->type = ErrorLogType::tryFrom(config('error-logger.type', 'file'));
        $this->deduplicate = config('error-logger.deduplicate', false);
        $this->level = Level::fromName(config('error-logger.level', 'error'));

        $this->file_path = config('error-logger.file.path', 'logs/laravel.log');

        $this->daily_file_path = config('error-logger.daily_file.path', 'logs/log_{timespan}.log');
        $this->daily_file_days = config('error-logger.daily_file.days', 7);

        $this->email_default_subject = config('error-logger.email.default_subject', 'Log');
        $this->email_to = config('error-logger.email.to', []);
        $this->email_from = config('error-logger.email.from', []);
        $this->email_reply_to = config('error-logger.email.reply_to', []);
        $this->email_priority = ErrorLogEmailPriority::tryFrom(strtolower(config('error-logger.email.priority', 'normal'))) ?? ErrorLogEmailPriority::Normal;

        $this->discord_webhook_url = config('error-logger.discord.webhook_url', '');
        $this->discord_username = config('error-logger.discord.default_username', 'Logger');
        $this->discord_avatar_url = config('error-logger.discord.avatar_url', '');

        $this->whatsapp = config('error-logger.whatsapp', []);

        $this->github_url = config('error-logger.github.url', '');
        $this->github_token = config('error-logger.github.token', '');

        $this->gitlab_url = config('error-logger.gitlab.url', '');
        $this->gitlab_token = config('error-logger.gitlab.token', '');

        $this->telegram_token = config('error-logger.telegram.token', '');
        $this->telegram_chat_id = config('error-logger.telegram.chat_id', '');
    }
}
