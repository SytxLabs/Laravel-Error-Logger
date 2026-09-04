# Error-Logger for Laravel

[![MIT Licensed](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Check code style](https://github.com/SytxLabs/Laravel-Error-Logger/actions/workflows/code-style.yml/badge.svg?style=flat-square)](https://github.com/SytxLabs/Laravel-Error-Logger/actions/workflows/code-style.yml)
[![Latest Version on Packagist](https://poser.pugx.org/sytxlabs/laravel-error-logger/v/stable?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-error-logger)
[![Total Downloads](https://poser.pugx.org/sytxlabs/laravel-error-logger/downloads?format=flat-square)](https://packagist.org/packages/sytxlabs/laravel-error-logger)


This package adds a basic logging channel that sends error logs to an email address, discord channel, whatsapp account, telegram chat and a (github/gitlab) issue.

## Prerequisites

* A configured default Laravel mail driver
* PHP 8.2 or higher
* Laravel 10.0 or higher

## Installation

```sh
composer require sytxlabs/laravel-error-logger
```

## Configuration

To configure your Laravel application to use the logger, you should create a logging channel in your `logging.php`
configuration file.

For example a stack channel that logs to the default stack and sends email notifications:

```php
return [
    // ...
    'channels' => [
        // ...    

        'error-log' => [
            'driver' => 'monolog',
            'handler' => \SytxLabs\ErrorLogger\Logging\Monolog\ErrorLogHandler::class,
        ],
    ],
    // ...    
];
```

You may then set the logging channel in your `.env` file or as the default logging channel in your `logging.php`.

```dotenv
LOG_CHANNEL=error-log
```

### Customization

The library offers some customization for the default `error-log` channel via a config.

It's also possible to publish the configuration for this package with the `artisan vendor:publish` command.

```sh
php artisan vendor:publish --tag=error-logger-config
```

### Formatters

Every log type accepts its own `formatter` option, which replaces the Monolog formatter used for that type:

```php
return [
    // ...
    'file' => [
        'path' => storage_path('logs/laravel.log'),
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
    // ...
];
```

The option accepts three kinds of values:

| Value                                 | Behaviour                                                                                                                                                                                                                |
|---------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| A class name (`JsonFormatter::class`) | Resolved through the service container, so the formatter is built with its own defaults or with your container binding. Without a usable container the constructor is called directly, as long as it needs no arguments. |
| A `FormatterInterface` instance       | Used as is, which is the way to pass constructor arguments.                                                                                                                                                              |
| A callable returning a formatter      | Called lazily, useful when the formatter needs the application to be booted.                                                                                                                                             |

Anything that cannot be resolved to a `Monolog\Formatter\FormatterInterface` is ignored and the default formatter of
that type is used, so a broken formatter configuration never breaks logging itself.

> Closures inside a config file prevent `php artisan config:cache` from working. If you cache your configuration, use
> a class name and bind the formatter in a service provider instead.

The defaults per type are:

| Type                                       | Default formatter                                                   |
|--------------------------------------------|---------------------------------------------------------------------|
| `file`, `daily_file`, `stdout`, `stderr`   | `\Monolog\Formatter\LineFormatter`                                  |
| `email`                                    | `\Monolog\Formatter\HtmlFormatter`                                  |
| `discord`                                  | `\SytxLabs\ErrorLogger\Logging\Handlers\Formatter\DiscordFormatter` |
| `github`, `gitlab`, `telegram`, `whatsapp` | `\SytxLabs\ErrorLogger\Logging\Handlers\Formatter\IssueFormatter`   |

The `webhook` type has no `formatter` option; its payload is built from the `format` option (`json`, `form`, `xml`,
`none`) instead.

## Known issues

### Mail drivers using a 'log' transport

Mail drivers using a `\Illuminate\Mail\Transport\LogTransport` transport are not supported and the EmailHandler will
fall back to a `NoopHandler`.

**However**, this automatic fallback currently only works if the selected driver directly uses a `LogTransport`.
If you for example set a `RoundRobinTransport` with a `LogTransport` mail driver, it will end up in
an infinite recursion loop. 
