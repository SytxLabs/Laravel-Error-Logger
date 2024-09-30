<?php

namespace SytxLabs\ErrorLogger\Logging\Handlers;

use function config;

use Illuminate\Support\Facades\Mail;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NoopHandler;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\ProcessableHandlerTrait;
use Monolog\Handler\SymfonyMailerHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use SytxLabs\ErrorLogger\Enums\ErrorLogEmailPriority;
use SytxLabs\ErrorLogger\Logging\Handlers\Traits\CorrectHandlerInterface;

class EmailHandler implements HandlerInterface, ProcessableHandlerInterface
{
    use CorrectHandlerInterface;
    use ProcessableHandlerTrait;

    private HandlerInterface $handler;

    public function __construct(string $subject, Level $level)
    {
        $recipient = config('error-logger.email.to', []);
        if (empty($recipient) || count($recipient) < 1 || empty($recipient[0] ?? null)) {
            $this->handler = new NoopHandler();
            return;
        }
        if (config('error-logger.email.drive', 'log') === 'log' || (config('error-logger.email.drive') === null && config('mail.default', 'log') === 'log')) {
            $this->handler = new NoopHandler();
            return;
        }
        $email = new Email();
        if (trim(config('error-logger.email.from.address', '')) !== '') {
            $email->from(new Address(config('error-logger.email.from.address', ''), config('error-logger.email.from.name', '')));
        }
        foreach ($recipient as $to) {
            if (!is_array($to) || !array_key_exists('address', $to)) {
                continue;
            }
            $email->addTo(new Address($to['address'], $to['name'] ?? ''));
        }
        if (empty($email->getTo())) {
            $this->handler = new NoopHandler();
            return;
        }
        if (config('error-logger.email.reply_to.address', '') !== '') {
            $email->replyTo(new Address(config('error-logger.email.reply_to.address', ''), config('error-logger.email.reply_to.name', '')));
        }
        $email->subject($subject);
        $email->priority((ErrorLogEmailPriority::tryFrom(config('error-logger.email.priority', ErrorLogEmailPriority::Normal->value)) ?? ErrorLogEmailPriority::Normal)->getPriority());
        $driver = config('error-logger.email.drive');
        if ($driver === null) {
            $driver = config('mail.default', 'log');
        }
        if ($driver === 'log') {
            $this->handler = new NoopHandler();
            return;
        }
        $mailHandler = new SymfonyMailerHandler(
            Mail::driver($driver)->getSymfonyTransport(),
            $email,
            $level,
        );
        $mailHandler->setFormatter(new HtmlFormatter('Y-m-d H:i:s'));
        $this->handler = $this->getCorrectHandler($mailHandler, $level);
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
