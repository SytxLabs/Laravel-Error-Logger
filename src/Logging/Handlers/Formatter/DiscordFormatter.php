<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\Formatter;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use Monolog\Utils;
use SytxLabs\ErrorLogger\Support\DiscordWebhook;

class DiscordFormatter extends NormalizerFormatter
{
    public function __construct(?string $dateFormat = null, private readonly ?DiscordWebhook $discordWebhook = null)
    {
        parent::__construct($dateFormat);
    }

    public function format(LogRecord $record): string
    {
        $this->discordWebhook?->setTitle($record->level->name . ' Log');
        $output = 'Message: `' . $record->message . '`' . PHP_EOL;
        $output .= 'Time: `' . $this->formatDate($record->datetime) . '`' . PHP_EOL;
        $output .= 'Channel: `' . $record->channel . '`' . PHP_EOL;
        if (count($record->context) > 0) {
            $output .= 'Context: ' . PHP_EOL . '---------------------' . PHP_EOL;
            foreach ($record->context as $key => $value) {
                $output .= '  ' . $key . ': ' . PHP_EOL . '```json' . PHP_EOL;
                $output .= $this->convertToString($value) . PHP_EOL . '```' . PHP_EOL . PHP_EOL;
            }
            $output .= '---------------------' . PHP_EOL;
        }
        if (count($record->extra) > 0) {
            $output .= 'Extra: ' . PHP_EOL . '---------------------' . PHP_EOL;
            foreach ($record->extra as $key => $value) {
                $output .= '  ' . $key . ': ' . PHP_EOL . '```json' . PHP_EOL;
                $output .= $this->convertToString($value) . PHP_EOL . '```' . PHP_EOL . PHP_EOL;
            }
            $output .= '---------------------' . PHP_EOL;
        }
        $this->discordWebhook?->setTxt($output);
        return $output;
    }

    protected function convertToString(mixed $data): string
    {
        if (null === $data || is_scalar($data)) {
            return (string) $data;
        }
        return Utils::jsonEncode($this->normalize($data), Utils::DEFAULT_JSON_FLAGS | JSON_PRETTY_PRINT, true);
    }

    public function formatBatch(array $records): string
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }
        return $message;
    }
}
