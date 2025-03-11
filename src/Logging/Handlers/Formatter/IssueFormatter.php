<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers\Formatter;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use Monolog\Utils;

class IssueFormatter extends NormalizerFormatter
{
    public function format(LogRecord $record): string
    {
        $output = '## Message: ' . PHP_EOL . '`' . $record->message . '`' . PHP_EOL .
            '## Time: ' . PHP_EOL . '`' . $this->formatDate($record->datetime) . '`' . PHP_EOL .
            '## Channel: ' . PHP_EOL . '`' . $record->channel . '`' . PHP_EOL.
            '## Level: ' . PHP_EOL . '`' . $record->level->getName() . '`' . PHP_EOL;
        if (count($record->context) > 0) {
            $output .= '## Context: ' . PHP_EOL . '```json' . PHP_EOL;
            foreach ($record->context as $key => $value) {
                $output .= $key . ': ' . $this->stringify($value) . PHP_EOL;
            }
            $output .= '```' . PHP_EOL;
        }
        if (count($record->extra) > 0) {
            $output .= '## Extra: ' . PHP_EOL . '```' . PHP_EOL;
            foreach ($record->extra as $key => $value) {
                $output .= $key . ': ' . $this->stringify($value) . PHP_EOL;
            }
            $output .= '```' . PHP_EOL;
        }
        return $output;
    }

    protected function stringify(mixed $value): string
    {
        if (is_string($value) || is_scalar($value) || $value === null) {
            return (string) ($value ?? 'null');
        }
        return Utils::jsonEncode($this->normalize($value), Utils::DEFAULT_JSON_FLAGS | JSON_PRETTY_PRINT, true);
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
