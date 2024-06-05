<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers;

use Illuminate\Support\Facades\Mail;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\DeduplicationHandler;
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
use SytxLabs\ErrorLogger\Support\Config;

class EmailHandler implements HandlerInterface, ProcessableHandlerInterface
{
    use ProcessableHandlerTrait;

    private HandlerInterface $handler;

    public function __construct(string $subject, bool $deduplicate, Level $level, Config $config)
    {
        $recipient = $config->email_to;
        if (empty($recipient) || count($recipient) < 1 || empty($recipient[0] ?? null) || empty($recipient[0]['address'] ?? null)) {
            $this->handler = new NoopHandler();
            return;
        }
        $email = new Email();
        $email->from(new Address($config->email_from['address'] ?? '', $config->email_from['name'] ?? ''));
        foreach ($recipient as $to) {
            if (!is_array($to) || !array_key_exists('address', $to)) {
                continue;
            }
            $email->addTo(new Address($to['address'], $to['name'] ?? ''));
        }
        $email->replyTo($config->email_reply_to['address'] ?? '', $config->email_reply_to['name'] ?? '');
        $email->subject($subject);
        $email->priority($config->email_priority->getPriority());

        $mailHandler = new SymfonyMailerHandler(
            $config->email_transport ?? Mail::getSymfonyTransport(),
            $email,
            $level,
        );

        $mailHandler->setFormatter(new HtmlFormatter('Y-m-d H:i:s'));

        $bufferHandlerClass = $deduplicate ? DeduplicationHandler::class : BufferHandler::class;
        $this->handler = new WhatFailureGroupHandler([
            new $bufferHandlerClass(new FingersCrossedHandler(
                $mailHandler,
                new ErrorLevelActivationStrategy($level), // Buffer all until configured level is reached.
            )),
        ]);
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
        return $this->handler->handle($record);
    }

    public function handleBatch(array $records): void
    {
        $this->handler->handleBatch($records);
    }

    public function close(): void
    {
        $this->handler->close();
    }
}
