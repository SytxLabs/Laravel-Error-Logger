<?php

namespace SytxLabs\ErrorLogger\Enums;

use Illuminate\Support\Carbon;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use SytxLabs\ErrorLogger\Logging\Handlers\EmailHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\InterfaceHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\DiscordProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\GithubProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\GitlabProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\TelegramProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\WebhookProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\WhatsappProcessingHandler;

enum ErrorLogType: string
{
    case File = 'file';
    case DailyFile = 'daily_file';
    case Email = 'email';
    case Discord = 'discord';
    case WhatsApp = 'whatsapp';
    case GitHub = 'github';
    case GitLab = 'gitlab';
    case Telegram = 'telegram';
    case Webhook = 'webhook';
    case Stdout = 'stdout';
    case Stderr = 'stderr';

    public function getHandler(string $subject, Level $level): HandlerInterface
    {
        $defaultFormat = new LineFormatter(null, 'd.m.Y H:i:s T', true, false, true);
        $handler = match ($this) {
            self::DailyFile => static function () use ($level, $defaultFormat) {
                $name = storage_path(config('error-logger.daily_file.path', 'logs/log_{timespan}.log'));
                if (str_contains($name, '{timespan}')) {
                    $days = config('error-logger.daily_file.days', 7);
                    $name = str_replace(
                        '{timespan}',
                        $days > 1 ? Carbon::now()->format('Y-m-d') . '_' . Carbon::now()->addDays($days - 1)->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                        $name
                    );
                }
                return new InterfaceHandler((new StreamHandler($name, $level))->setFormatter($defaultFormat), $level);
            },
            self::Email => new EmailHandler($subject, $level),
            self::Discord => new InterfaceHandler(new DiscordProcessingHandler($level), $level),
            self::WhatsApp => new InterfaceHandler(new WhatsappProcessingHandler($level), $level),
            self::GitHub => new InterfaceHandler(new GithubProcessingHandler($level), $level),
            self::GitLab => new InterfaceHandler(new GitlabProcessingHandler($level), $level),
            self::Telegram => new InterfaceHandler(new TelegramProcessingHandler($level), $level),
            self::Webhook => new InterfaceHandler(new WebhookProcessingHandler($level), $level),
            self::Stdout => new InterfaceHandler((new StreamHandler('php://stdout', $level))->setFormatter($defaultFormat), $level),
            self::Stderr => new InterfaceHandler((new StreamHandler('php://stderr', $level))->setFormatter($defaultFormat), $level),
            default => new InterfaceHandler((new StreamHandler(config('error-logger.file.path'), $level))->setFormatter($defaultFormat), $level),
        };
        return value($handler);
    }
}
