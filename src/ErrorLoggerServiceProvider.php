<?php

namespace SytxLabs\ErrorLogger;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use SytxLabs\ErrorLogger\Logging\LogManager;

class ErrorLoggerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/error-logger.php' => config_path('error-logger.php'),
            ], 'error-logger-config');
            $this->mergeConfigFrom(__DIR__ . '/../config/channels.php', 'logging.channels');

            AboutCommand::add('SytxLabs Error Log Package', static fn () => ['Version' => '1.0.0', 'Author' => 'SytxLabs']);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/error-logger.php', 'error-logger');
        $this->mergeConfigFrom(__DIR__ . '/../config/channels.php', 'logging.channels');
        $this->app->bind('error-log', static fn () => new LogManager($this->app));
    }
}
