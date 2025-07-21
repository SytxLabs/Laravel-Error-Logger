<?php

return [
    'types' => ['file'], // file, daily_file, email, discord, whatsapp, github, gitlab, telegram, webhook
    'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'

    'deduplicate' => [
        'enabled' => true,
        'interval' => 60, // in seconds
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        'path' => storage_path('logs/deduplicate.log'), // path to store deduplicate log can be null for no log
    ],

    'file' => [
        'path' => storage_path('logs/log.log'),
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],

    'daily_file' => [
        'path' => storage_path('logs/log_{timespan}.log'),
        'days' => 7,
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
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
        'drive' => null, // null, smtp, log,
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
    'discord' => [
        'webhook_url' => '',
        'username' => 'Logger',
        'avatar_url' => '',
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
    'whatsapp' => [
        [
            'phone_number' => '', // This System is using CallMeBot API to send messages to WhatsApp. https://www.callmebot.com/blog/free-api-whatsapp-messages/
            'api_token' => '',
        ],
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
    'github' => [
        'url' => '', // https://gitlab.com/username/project
        'token' => '', // Personal Access Token
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
    'gitlab' => [
        'url' => '', // https://gitlab.com/username/project
        'token' => '', // Personal Access Token
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
    'telegram' => [
        'token' => '', // Bot Token
        'chat_id' => '', // Chat ID
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
    'webhook' => [
        'url' => '',
        'secret_type' => null, // null, basic, token, bearer
        'secret' => null, // for basic a array of username and password, for token a string token, for bearer a string token
        'method' => 'POST',
        'format' => 'json', // json, form_params, xml, none
        'level' => 'debug', // 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    ],
];
