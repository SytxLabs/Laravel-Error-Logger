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
    ],

    'daily_file' => [
        'path' => storage_path('logs/log_{timespan}.log'),
        'days' => 7,
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
    ],
    'discord' => [
        'webhook_url' => '',
        'username' => 'Logger',
        'avatar_url' => '',
    ],
    'whatsapp' => [
        [
            'phone_number' => '', // This System is using CallMeBot API to send messages to WhatsApp. https://www.callmebot.com/blog/free-api-whatsapp-messages/
            'api_token' => '',
        ],
    ],
    'github' => [
        'url' => '', // https://gitlab.com/username/project
        'token' => '', // Personal Access Token
    ],
    'gitlab' => [
        'url' => '', // https://gitlab.com/username/project
        'token' => '', // Personal Access Token
    ],
    'telegram' => [
        'token' => '', // Bot Token
        'chat_id' => '', // Chat ID
    ],
    'webhook' => [
        'url' => '',
        'secret_type' => null, // null, basic, token, bearer
        'secret' => null, // for basic a array of username and password, for token a string token, for bearer a string token
        'method' => 'POST',
        'format' => 'json', // json, form_params, xml
    ],
];
