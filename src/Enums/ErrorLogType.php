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
use SytxLabs\ErrorLogger\Support\Config;

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

    public function getHandler(string $subject, Level $level, Config $config): HandlerInterface
    {
        return match ($this) {
            self::File => new InterfaceHandler(new StreamHandler($config->file_path, $level), $level, $config),
            self::DailyFile => new DailyFileHandler($level, $config),
            self::Email => new EmailHandler($subject, $level, $config),
            self::Discord => new InterfaceHandler(new DiscordProcessingHandler($level, $config), $level, $config),
            self::WhatsApp => new InterfaceHandler(new WhatsappProcessingHandler($level, $config), $level, $config),
            self::GitHub => new InterfaceHandler(new GithubProcessingHandler($level, $config), $level, $config),
            self::GitLab => new InterfaceHandler(new GitlabProcessingHandler($level, $config), $level, $config),
            self::Telegram => new InterfaceHandler(new TelegramProcessingHandler($level, $config), $level, $config),
        };
    }
}
