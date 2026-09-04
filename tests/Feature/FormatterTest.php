<?php

namespace SytxLabs\ErrorLogger\Tests\Feature;

use Monolog\DateTimeImmutable;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use stdClass;
use SytxLabs\ErrorLogger\ErrorLoggerServiceProvider;
use SytxLabs\ErrorLogger\Logging\Handlers\ProcessingHandler\WhatsappProcessingHandler;
use SytxLabs\ErrorLogger\Logging\LogManager;
use SytxLabs\ErrorLogger\Support\FormatterResolver;
use SytxLabs\ErrorLogger\Tests\Fixtures\AbstractFormatterStub;
use SytxLabs\ErrorLogger\Tests\Fixtures\MarkerFormatter;
use SytxLabs\ErrorLogger\Tests\Fixtures\RequiredArgumentFormatter;

class FormatterTest extends TestCase
{
    private string $logPath = '';

    protected function getPackageProviders($app): array
    {
        return [ErrorLoggerServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->logPath = sys_get_temp_dir() . '/error-logger-formatter-' . uniqid('', true) . '.log';
        config(['error-logger.types' => ['file'], 'error-logger.file.path' => $this->logPath, 'error-logger.deduplicate.enabled' => false]);
    }

    protected function tearDown(): void
    {
        if ($this->logPath !== '' && file_exists($this->logPath)) {
            unlink($this->logPath);
        }
        parent::tearDown();
    }

    private function logLine(): string
    {
        (new LogManager($this->app))->log(Level::Error, 'formatter message');
        $this->assertFileExists($this->logPath);
        return file_get_contents($this->logPath) ?: '';
    }

    public function test_it_uses_the_default_formatter_when_none_is_configured(): void
    {
        $this->assertStringContainsString('formatter message', $this->logLine());
        $this->assertStringNotContainsString('MARKER', $this->logLine());
    }

    public function test_it_uses_a_formatter_configured_as_class_name(): void
    {
        config(['error-logger.file.formatter' => MarkerFormatter::class]);

        $this->assertStringContainsString('MARKER formatter message', $this->logLine());
    }

    public function test_it_uses_a_formatter_configured_as_instance(): void
    {
        config(['error-logger.file.formatter' => new MarkerFormatter('INSTANCE')]);

        $this->assertStringContainsString('INSTANCE formatter message', $this->logLine());
    }

    public function test_it_uses_a_formatter_configured_as_closure(): void
    {
        config(['error-logger.file.formatter' => static fn () => new MarkerFormatter('CLOSURE')]);

        $this->assertStringContainsString('CLOSURE formatter message', $this->logLine());
    }

    public function test_it_supports_monolog_formatters_with_a_different_constructor(): void
    {
        config(['error-logger.file.formatter' => JsonFormatter::class]);

        $this->assertStringContainsString('"message":"formatter message"', $this->logLine());
    }

    public function test_it_falls_back_to_the_default_formatter_on_invalid_configuration(): void
    {
        config(['error-logger.file.formatter' => 'Not\\A\\Formatter']);

        $this->assertStringContainsString('formatter message', $this->logLine());
    }

    public function test_the_resolver_falls_back_for_unusable_values(): void
    {
        $default = static fn (): LineFormatter => new LineFormatter();

        $this->assertInstanceOf(LineFormatter::class, FormatterResolver::resolve(null, $default));
        $this->assertInstanceOf(LineFormatter::class, FormatterResolver::resolve(stdClass::class, $default));
        $this->assertInstanceOf(LineFormatter::class, FormatterResolver::resolve(42, $default));
        $this->assertInstanceOf(LineFormatter::class, FormatterResolver::resolve(static fn () => 'not a formatter', $default));
        $this->assertInstanceOf(LineFormatter::class, FormatterResolver::resolve(static fn () => throw new RuntimeException('boom'), $default));
    }

    public function test_the_resolver_returns_configured_formatters(): void
    {
        $default = static fn (): LineFormatter => new LineFormatter();
        $instance = new MarkerFormatter();

        $this->assertSame($instance, FormatterResolver::resolve($instance, $default));
        $this->assertInstanceOf(MarkerFormatter::class, FormatterResolver::resolve(MarkerFormatter::class, $default));
        $this->assertInstanceOf(MarkerFormatter::class, FormatterResolver::resolve(static fn () => new MarkerFormatter(), $default));
    }

    public function test_it_resolves_class_names_through_the_container(): void
    {
        $this->app->bind(MarkerFormatter::class, static fn () => new MarkerFormatter('BOUND'));
        config(['error-logger.file.formatter' => MarkerFormatter::class]);

        $this->assertStringContainsString('BOUND formatter message', $this->logLine());
    }

    public function test_the_resolver_falls_back_for_class_names_it_cannot_build(): void
    {
        $default = static fn (): LineFormatter => new LineFormatter();

        $this->assertInstanceOf(LineFormatter::class, FormatterResolver::resolve(RequiredArgumentFormatter::class, $default));
        $this->assertInstanceOf(LineFormatter::class, FormatterResolver::resolve(AbstractFormatterStub::class, $default));
    }

    public function test_whatsapp_handler_ignores_option_keys_in_its_configuration(): void
    {
        config(['error-logger.whatsapp' => [
            ['phone_number' => '+490000000', 'api_token' => 'token'],
            'level' => 'debug',
            'formatter' => MarkerFormatter::class,
            'deduplicate' => ['enabled' => false],
        ]]);

        $handler = new WhatsappProcessingHandler(Level::Debug);

        $this->assertTrue($handler->isHandling(new LogRecord(new DateTimeImmutable(true), 'testing', Level::Error, 'message')));
    }
}
