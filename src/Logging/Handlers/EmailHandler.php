<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers;

use DateTimeImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NoopHandler;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\Handler\SymfonyMailerHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use SytxLabs\ErrorLogger\Enums\EmailLimitSentInterval;
use SytxLabs\ErrorLogger\Enums\ErrorLogEmailPriority;

class EmailHandler implements HandlerInterface, ProcessableHandlerInterface
{
    use ProcessableHandlerTrait;

    private HandlerInterface $handler;
    private ?EmailLimitSentInterval $limitSentInterval = null;

    public function __construct(string $subject, Level $level)
    {
        if (config('error-logger.email.drive', 'log') === 'log' || (config('error-logger.email.drive') === null && config('mail.default', 'log') === 'log')) {
            $this->handler = new NoopHandler();
            return;
        }
        $email = new Email();
        $email = $this->setRecipient($email);
        if ($email === null || empty($email->getTo()) || count($email->getTo()) < 1) {
            $this->handler = new NoopHandler();
            return;
        }
        $this->limitSentInterval = EmailLimitSentInterval::active();
        if ($this->limitSentInterval->isExited()) {
            $this->handler = new NoopHandler();
            if ($this->limitSentInterval->sendEmergencyLog()) {
                $email = $this->setFrom($email);
                $email = $this->setReplyTo($email)->subject('Error Logger Email Limit Exceeded')->priority(ErrorLogEmailPriority::High->getPriority());
                $driver = config('error-logger.email.drive') ?? config('mail.default', 'log');
                $mailHandler = new SymfonyMailerHandler(Mail::driver($driver)->getSymfonyTransport(), $email, Level::Critical);
                $mailHandler->setFormatter(new HtmlFormatter('Y-m-d H:i:s'));
                $handler = new WhatFailureGroupHandler([new FingersCrossedHandler($mailHandler, new ErrorLevelActivationStrategy(Level::Critical))]);
                $handler->handle(new LogRecord(
                    datetime: new DateTimeImmutable(),
                    channel: 'error-logger',
                    level: Level::Critical,
                    message: 'The email limit for error logging has been exceeded. Further emails will be suppressed until the limit interval resets.',
                    context: [
                        'limit' => ((int) config('error-logger.email.limit_sent.max_sent', 500)),
                        'now' => $this->limitSentInterval->collect()->count(),
                        'interval_type' => $this->limitSentInterval->value,
                        'interval' => ((int) config('error-logger.email.limit_sent.interval', 1)),
                    ],
                    extra: []
                ));
            }
            return;
        }
        $email = $this->setFrom($email);
        $email = $this->setReplyTo($email)->subject(Str::limit($subject, config('error-logger.email.max_subject_length', 75)))
            ->priority((ErrorLogEmailPriority::tryFrom(config('error-logger.email.priority', ErrorLogEmailPriority::Normal->value)) ?? ErrorLogEmailPriority::Normal)->getPriority());
        $driver = config('error-logger.email.drive') ?? config('mail.default', 'log');
        if ($driver === 'log') {
            $this->handler = new NoopHandler();
            return;
        }
        $mailHandler = new SymfonyMailerHandler(Mail::driver($driver)->getSymfonyTransport(), $email, $level);
        $mailHandler->setFormatter(new HtmlFormatter('Y-m-d H:i:s'));
        $this->handler = new WhatFailureGroupHandler([new FingersCrossedHandler($mailHandler, new ErrorLevelActivationStrategy($level))]);
    }

    public function isHandling(LogRecord $record): bool
    {
        return $this->handler->isHandling($record);
    }

    public function handle(LogRecord $record): bool
    {
        if (count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }
        $result = $this->handler->handle($record);
        $this->limitSentInterval?->recordSent();
        return $result;
    }

    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    public function close(): void
    {
        $this->handler->close();
    }

    private function setRecipient(Email $email): ?Email
    {
        $recipient = config('error-logger.email.to', []);
        if (is_string($recipient) && !empty($recipient)) {
            $recipient = array_map('trim', preg_split('/[;,]/', $recipient) ?? Arr::wrap($recipient));
        }
        if (empty($recipient) || count($recipient) < 1 || empty($recipient[0] ?? null)) {
            return null;
        }
        foreach ($recipient as $to) {
            if (!is_array($to) || !array_key_exists('address', $to)) {
                $to = ['address' => $to];
            }
            $email->addTo(new Address($to['address'], $to['name'] ?? ''));
        }
        return $email;
    }

    private function setFrom(Email $email): Email
    {
        $from = config('error-logger.email.from', []);
        if (is_string($from) && !empty($from)) {
            $from = ['address' => trim($from)];
        }
        if (!empty($from['address'])) {
            $email->from(new Address(trim($from['address']), trim($from['name'] ?? '')));
        } else {
            $email->from(new Address(config('mail.from.address', ''), config('mail.from.name', '')));
        }
        return $email;
    }

    private function setReplyTo(Email $email): Email
    {
        $replyTo = config('error-logger.email.reply_to', []);
        if (is_string($replyTo) && !empty($replyTo)) {
            $replyTo = ['address' => trim($replyTo)];
        }
        if (!empty($replyTo['address'])) {
            $email->replyTo(new Address(trim($replyTo['address']), trim($replyTo['name'] ?? '')));
        } else {
            $email->replyTo(new Address(config('mail.from.address', ''), config('mail.from.name', '')));
        }
        return $email;
    }
}
