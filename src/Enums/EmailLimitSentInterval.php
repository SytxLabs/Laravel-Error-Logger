<?php

namespace SytxLabs\ErrorLogger\Enums;

use BackedEnum;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

enum EmailLimitSentInterval: string
{
    case MINUTE = 'minute';
    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    public static function active(): self
    {
        $v = config('error-logger.email.limit_sent.interval_type', EmailLimitSentInterval::DAY->value);
        if ($v instanceof BackedEnum) {
            $v = $v->value;
        }
        return self::tryFrom($v) ?? self::DAY;
    }

    private function path(): string
    {
        return storage_path('logs/error-logger-email-limit-sent.lock');
    }

    public function subNow($now = null): Carbon
    {
        $now ??= now();
        $interval = ((int) config('error-logger.email.limit_sent.interval', 1)) - 1;
        if ($interval < 0) {
            $interval = 0;
        }
        return match ($this) {
            self::MINUTE => $now->startOfMinute()->subMinutes($interval),
            self::HOUR => $now->startOfHour()->subHours($interval),
            self::DAY => $now->startOfDay()->subDays($interval),
            self::WEEK => $now->startOfWeek()->subWeeks($interval),
            self::MONTH => $now->startOfMonth()->subMonths($interval),
            self::YEAR => $now->startOfYear()->subYears($interval),
        };
    }

    public function isExited(): bool
    {
        if (!config('error-logger.email.limit_sent.enabled', false)) {
            return false;
        }
        $sentTimestamps = $this->collect();
        return $sentTimestamps->count() >= ((int) config('error-logger.email.limit_sent.max_sent', 500));
    }

    public function sendEmergencyLog(): bool
    {
        if (!config('error-logger.email.limit_sent.enabled', false)) {
            return false;
        }
        if (!$this->isExited()) {
            return false;
        }
        $path = storage_path('logs/error-logger-email-limit-sent-emergency-log-sent-' . $this->value . '.lock');
        $fs = new Filesystem();
        if ($fs->exists($path)) {
            if (rescue(fn () => (int) $fs->get($path), 0, false) > $this->subNow()->getTimestamp()) {
                return false;
            }
        }
        $fs->put($path, now()->getTimestamp());
        return true;
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function collect(): Collection
    {
        if (!config('error-logger.email.limit_sent.enabled', false)) {
            return collect();
        }
        $fs = new Filesystem();
        if (!$fs->exists($this->path())) {
            return collect();
        }
        $timestamps = collect(explode(PHP_EOL, rescue(fn () => $fs->get($this->path()), '', false)))
            ->filter(static fn ($line) => (trim($line ?? '') !== '') && is_numeric(trim($line)))
            ->map(fn ($line) => (int) trim($line))->filter(fn ($timestamp) => $timestamp > ($this->subNow()->getTimestamp()));
        $fs->put($this->path(), $timestamps->implode(PHP_EOL));
        return $timestamps;
    }

    public function recordSent(): void
    {
        if (!config('error-logger.email.limit_sent.enabled', false)) {
            return;
        }
        $fs = new Filesystem();
        $timestamps = collect();
        if ($fs->exists($this->path())) {
            $timestamps = collect(explode(PHP_EOL, rescue(fn () => $fs->get($this->path()), '', false)))
                ->filter(static fn ($line) => (trim($line ?? '') !== '') && is_numeric(trim($line)))
                ->map(fn ($line) => (int) trim($line))->filter(fn ($timestamp) => $timestamp > ($this->subNow()->getTimestamp()));
        }
        $timestamps->push(now()->getTimestamp());
        $fs->put($this->path(), $timestamps->implode(PHP_EOL));
    }
}
