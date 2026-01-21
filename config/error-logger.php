<?php

return [
    'types' => ['file'], // file, daily_file, email, discord, whatsapp, github, gitlab, telegram, webhook, stdout, stderr
    'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'

    'deduplicate' => [
        'enabled' => true,
        'interval' => 60, // in seconds
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
    ],

    'file' => [
        'path' => storage_path('logs/laravel.log'),
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],

    'daily_file' => [
        'path' => storage_path('logs/laravel_{timespan}.log'),
        'days' => 7,
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'email' => [
        'default_subject' => 'Log',
        'to' => [
            [
                'address' => '',
                'name' => '', // optional
            ],
        ],
        'from' => [ // optional
            'address' => '',
            'name' => '',
        ],
        'reply_to' => [ // optional
            'address' => '',
            'name' => '',
        ],
        'priority' => 'normal', // normal, high, low
        'drive' => null, // null, smtp, log
        'max_subject_length' => 75,
        'limit_sent' => [
            'enabled' => false,
            'interval_type' => \SytxLabs\ErrorLogger\Enums\EmailLimitSentInterval::DAY->value,
            'interval' => 1,
            'max_sent' => 500, // max emails to send in the interval
        ],
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'discord' => [
        'webhook_url' => '',
        'username' => 'Logger',
        'avatar_url' => '',
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'whatsapp' => [
        [
            'phone_number' => '', // This System is using CallMeBot API to send messages to WhatsApp. https://www.callmebot.com/blog/free-api-whatsapp-messages/
            'api_token' => '',
        ],
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'github' => [
        'url' => '', // https://gitlab.com/username/project
        'token' => '', // Personal Access Token
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'gitlab' => [
        'url' => '', // https://gitlab.com/username/project
        'token' => '', // Personal Access Token
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'telegram' => [
        'token' => '', // Bot Token
        'chat_id' => '', // Chat ID
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'webhook' => [
        'url' => '',
        'secret_type' => null, // null, basic, token, bearer
        'secret' => null, // for basic a array of username and password, for token a string token, for bearer a string token
        'method' => 'POST',
        'format' => 'json', // json, form_params, xml, none
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'stdout' => [
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
    'stderr' => [
        // 'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        // 'deduplicate' => [
        //     'enabled' => true,
        //     'interval' => 60, // in seconds
        //     'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        //     'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
        // ],
    ],
];
