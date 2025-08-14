<?php

namespace SytxLabs\ErrorLogger\Tests\Feature;

use Monolog\Level;
use Orchestra\Testbench\TestCase;
use SytxLabs\ErrorLogger\ErrorLoggerServiceProvider;
use SytxLabs\ErrorLogger\Logging\LogManager;

class ErrorLoggerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ErrorLoggerServiceProvider::class];
    }

    public function test_it_logs_working_debug()
    {
        $logger = new LogManager($this->app);

        $logger->log(Level::Debug, 'message');
        $this->assertTrue(true);
    }

    public function test_it_logs_working_error()
    {
        $logger = new LogManager($this->app);

        $logger->log(Level::Error, 'message');
        $this->assertTrue(true);
    }
}
