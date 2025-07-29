<?php

namespace SytxLabs\ErrorLogger\Facade;

use Illuminate\Support\Facades\Facade;

class ErrorLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'error-log';
    }
}
