<?php

declare(strict_types=1);

return [
    'app_name' => 'Moonlight Cinema',
    'database' => [
        'driver' => getenv('MC_DB_DRIVER') ?: 'sqlite',
        'sqlite_path' => __DIR__ . '/../storage/moonlight.sqlite',
        'host' => getenv('MC_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('MC_DB_PORT') ?: '3306',
        'name' => getenv('MC_DB_NAME') ?: 'moonlight_cinema',
        'user' => getenv('MC_DB_USER') ?: 'root',
        'pass' => getenv('MC_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from' => getenv('MC_MAIL_FROM') ?: 'info@moonlightcinema.ba',
        'from_name' => getenv('MC_MAIL_FROM_NAME') ?: 'Moonlight Cinema',
        'reply_to' => getenv('MC_MAIL_REPLY_TO') ?: (getenv('MC_MAIL_FROM') ?: 'info@moonlightcinema.ba'),
        'transport' => getenv('MC_MAIL_TRANSPORT') ?: 'auto',
        'log_path' => __DIR__ . '/../storage/logs/mail.log',
        'smtp' => [
            'host' => getenv('MC_SMTP_HOST') ?: '',
            'port' => (int) (getenv('MC_SMTP_PORT') ?: 587),
            'username' => getenv('MC_SMTP_USERNAME') ?: '',
            'password' => getenv('MC_SMTP_PASSWORD') ?: '',
            'encryption' => getenv('MC_SMTP_ENCRYPTION') ?: 'tls',
            'timeout' => (int) (getenv('MC_SMTP_TIMEOUT') ?: 15),
        ],
    ],
];
