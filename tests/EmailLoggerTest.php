<?php

namespace SytxLabs\ErrorLogger\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Monolog\Level;
use Monolog\LogRecord;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Mailer\Transport\TransportInterface;
use SytxLabs\ErrorLogger\Enums\ErrorLogType;
use SytxLabs\ErrorLogger\Logging\Monolog\ErrorLogHandler;
use SytxLabs\ErrorLogger\Support\Config as ErrorLoggerConfig;

class EmailLoggerTest extends TestCase
{
    private ErrorLoggerConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $deduplicateStore = __DIR__ . '/deduplicate.log';

        if (file_exists($deduplicateStore) && is_writable($deduplicateStore)) {
            unlink($deduplicateStore);
        }
        Config::set('mail.default', 'smtp');
        $this->config = new ErrorLoggerConfig();
        $this->config->email_from = [
            'name' => 'Error Logger',
            'address' => 'error-logger@sytxlabs.eu',
        ];
        $this->config->email_reply_to = [
            'name' => 'Error Logger',
            'address' => 'error-logger@sytxlabs.eu',
        ];
        $this->config->type = ErrorLogType::Email;
    }

    public function testMailerSkippedIfRecipientsAreNotConfigured(): void
    {
        Mail::shouldReceive('driver')
            ->never();

        $this->config->email_to = [];
        $handler = new ErrorLogHandler($this->config);
        $record = new LogRecord(new CarbonImmutable(), 'default', Level::Error, 'Message');
        $handler->handle($record);
    }

    public function testEmailIsSent(): void
    {
        Mail::shouldReceive('driver')
            ->withArgs(['smtp'])
            ->once()
            ->andReturnUsing(
                fn () => Mockery::mock(Mailer::class)
                    ->shouldReceive('getSymfonyTransport')
                    ->once()
                    ->andReturnUsing(
                        fn () => Mockery::mock(TransportInterface::class)
                            ->shouldReceive('send')
                            ->once()
                            ->getMock()
                    )
                    ->getMock()
            );

        $this->config->email_to = [
            ['name' => 'John Doe', 'address' => 'john.doe@example.org'],
        ];
        $this->config->level = Level::Error;
        $handler = new ErrorLogHandler($this->config);
        $record = new LogRecord(CarbonImmutable::now(), 'smtp', Level::Error, 'Message');

        $handler->handle($record);
    }

    public function testEmailIsNotSentIfLogLevelIsTooLow(): void
    {
        Mail::shouldReceive('driver')
            ->once()
            ->andReturnUsing(
                fn () => Mockery::mock(Mailer::class)
                    ->shouldReceive('getSymfonyTransport')
                    ->once()
                    ->andReturnUsing(
                        fn () => Mockery::mock(TransportInterface::class)
                            ->shouldReceive('send')
                            ->never()
                            ->getMock()
                    )
                    ->getMock()
            );

        $this->config->email_to = [
            ['name' => 'John Doe', 'address' => 'john.doe@example.org'],
        ];
        $this->config->level = Level::Error;
        $this->config->deduplicate_enabled = false;
        $handler = new ErrorLogHandler($this->config);
        $record = new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message');
        $handler->handle($record);
    }

    public function testEmailIsSentUntilLogLevelAppears(): void
    {
        Mail::shouldReceive('driver')
            ->once()
            ->andReturnUsing(
                fn () => Mockery::mock(Mailer::class)
                    ->shouldReceive('getSymfonyTransport')
                    ->once()
                    ->andReturnUsing(
                        fn () => Mockery::mock(TransportInterface::class)
                            ->shouldReceive('send')
                            ->once()
                            ->getMock()
                    )
                    ->getMock()
            );

        $this->config->email_to = [
            ['name' => 'John Doe', 'address' => 'john.doe@example.org'],
        ];
        $this->config->level = Level::Error;
        $this->config->deduplicate_enabled = false;
        $handler = new ErrorLogHandler($this->config);

        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Info, 'Info Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Error, 'Error Message'));
    }

    public function testEmailsAreDeduplicated(): void
    {
        Mail::shouldReceive('driver')
            ->once()
            ->andReturnUsing(
                fn () => Mockery::mock(Mailer::class)
                    ->shouldReceive('getSymfonyTransport')
                    ->once()
                    ->andReturnUsing(
                        fn () => Mockery::mock(TransportInterface::class)
                            ->shouldReceive('send')
                            ->once()
                            ->getMock()
                    )
                    ->getMock()
            );

        $this->config->email_to = [
            ['name' => 'John Doe', 'address' => 'john.doe@example.org'],
        ];
        $this->config->deduplicate_enabled = true;
        $this->config->level = Level::Error;
        $handler = new ErrorLogHandler($this->config);

        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Info, 'Info Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Error, 'Error Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Info, 'Info Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Error, 'Error Message'));
    }

    public function testEmailsAreNotDeduplicatedByDefault(): void
    {
        Mail::shouldReceive('driver')
            ->once()
            ->andReturnUsing(
                fn () => Mockery::mock(Mailer::class)
                    ->shouldReceive('getSymfonyTransport')
                    ->once()
                    ->andReturnUsing(
                        fn () => Mockery::mock(TransportInterface::class)
                            ->shouldReceive('send')
                            ->twice()
                            ->getMock()
                    )
                    ->getMock()
            );

        $this->config->email_to = [
            ['name' => 'John Doe', 'address' => 'john.doe@example.org'],
        ];
        $this->config->deduplicate_enabled = false;
        $this->config->level = Level::Error;
        $handler = new ErrorLogHandler($this->config);

        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Info, 'Info Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Error, 'Error Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Info, 'Info Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Error, 'Error Message'));
    }

    public function testLogTransportIsNotHandled(): void
    {
        Mail::shouldReceive('driver')
            ->never();

        $this->config->email_to = [
            ['name' => 'John Doe', 'address' => 'john.doe@example.org'],
        ];
        $this->config->deduplicate_enabled = true;
        $this->config->level = Level::Error;
        $this->config->type = ErrorLogType::File;
        $handler = new ErrorLogHandler($this->config);

        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Info, 'Info Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Error, 'Error Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Info, 'Info Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Warning, 'Warning Message'));
        $handler->handle(new LogRecord(CarbonImmutable::now(), 'default', Level::Error, 'Error Message'));
    }
}
