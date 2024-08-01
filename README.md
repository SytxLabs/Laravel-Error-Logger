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

## Known issues

### Mail drivers using a 'log' transport

Mail drivers using a `\Illuminate\Mail\Transport\LogTransport` transport are not supported and the EmailHandler will
fall back to a `NoopHandler`.

**However**, this automatic fallback currently only works if the selected driver directly uses a `LogTransport`.
If you for example set a `RoundRobinTransport` with a `LogTransport` mail driver, it will end up in
an infinite recursion loop. 
