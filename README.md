# Error-Logger

This is a error logger for laravel. It logs the errors and sends an email to the admin.

## Installation

You can install the package via composer:

```bash
composer require sytxlabs/error-logger
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Sytxlabs\ErrorLogger\ErrorLoggerServiceProvider" --tag="config"
```

After publishing the config file, change the values in the config file to your desired values.
And also add the following to your .env file

```bash
LOG_CHANNEL=error-logger
```