<?php

return [
    'error-log' => [
        'driver' => 'monolog',
        'handler' => SytxLabs\ErrorLogger\Logging\Monolog\ErrorLogHandler::class,
    ],
];
