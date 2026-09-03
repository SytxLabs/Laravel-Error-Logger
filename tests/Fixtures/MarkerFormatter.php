<?php

namespace SytxLabs\ErrorLogger\Tests\Fixtures;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

readonly class MarkerFormatter implements FormatterInterface
{
    public function __construct(private string $marker = 'MARKER')
    {
    }

    public function format(LogRecord $record): string
    {
        return $this->marker . ' ' . $record->message . PHP_EOL;
    }

    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }
        return $output;
    }
}
