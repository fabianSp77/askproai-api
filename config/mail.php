<?php
return [
    'default' => env('MAIL_MAILER', 'smtp'),
    'mailers' => [
        'smtp' => [
            'transport'  => 'smtp',
            'host'       => env('MAIL_HOST'),
            'port'       => env('MAIL_PORT'),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username'   => env('MAIL_USERNAME'),
            'password'   => env('MAIL_PASSWORD'),
            'timeout'    => 10,
        ],
        'resend' => [
            'transport' => 'resend',
            'key' => env('RESEND_API_KEY'),
            'queue' => 'emails',
        ],
        'log' => ['transport' => 'log'],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS'),
        'name'    => env('MAIL_FROM_NAME'),
    ],
];
