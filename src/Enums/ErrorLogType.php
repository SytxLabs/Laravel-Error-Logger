<?php

namespace SytxLabs\ErrorLogger\Enums;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use SytxLabs\ErrorLogger\Logging\Handlers\DailyFileHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\EmailHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\InterfaceHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\DiscordProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\GithubProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\GitlabProcessingHandler;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\TelegramProcessingHandler;
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

    public function getHandler(string $subject, Level $level): HandlerInterface
    {
        return match ($this) {
            self::File => new InterfaceHandler(new StreamHandler(config('error-logger.file.path'), $level), $level),
            self::DailyFile => new DailyFileHandler($level),
            self::Email => new EmailHandler($subject, $level),
            self::Discord => new InterfaceHandler(new DiscordProcessingHandler($level), $level),
            self::WhatsApp => new InterfaceHandler(new WhatsappProcessingHandler($level), $level),
            self::GitHub => new InterfaceHandler(new GithubProcessingHandler($level), $level),
            self::GitLab => new InterfaceHandler(new GitlabProcessingHandler($level), $level),
            self::Telegram => new InterfaceHandler(new TelegramProcessingHandler($level), $level),
        };
    }
}
