<?php

declare(strict_types=1);

// Lokalne postavke (npr. za InfinityFree, gdje nema environment varijabli).
// app/config.local.php se NE commita na GitHub.
$localConfig = is_file(__DIR__ . '/config.local.php') ? (require __DIR__ . '/config.local.php') : [];
$env = static fn (string $key) => $localConfig[$key] ?? getenv($key);

return [
    'app_name' => 'Moonlight Cinema',
    'database' => [
        'driver' => $env('MC_DB_DRIVER') ?: 'sqlite',
        'sqlite_path' => __DIR__ . '/../storage/moonlight.sqlite',
        'host' => $env('MC_DB_HOST') ?: '127.0.0.1',
        'port' => $env('MC_DB_PORT') ?: '3306',
        'name' => $env('MC_DB_NAME') ?: 'moonlight_cinema',
        'user' => $env('MC_DB_USER') ?: 'root',
        'pass' => $env('MC_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from' => $env('MC_MAIL_FROM') ?: 'info@moonlightcinema.ba',
        'from_name' => $env('MC_MAIL_FROM_NAME') ?: 'Moonlight Cinema',
        'reply_to' => $env('MC_MAIL_REPLY_TO') ?: ($env('MC_MAIL_FROM') ?: 'info@moonlightcinema.ba'),
        'transport' => $env('MC_MAIL_TRANSPORT') ?: 'auto',
        'log_path' => __DIR__ . '/../storage/logs/mail.log',
        'smtp' => [
            'host' => $env('MC_SMTP_HOST') ?: '',
            'port' => (int) ($env('MC_SMTP_PORT') ?: 587),
            'username' => $env('MC_SMTP_USERNAME') ?: '',
            'password' => $env('MC_SMTP_PASSWORD') ?: '',
            'encryption' => $env('MC_SMTP_ENCRYPTION') ?: 'tls',
            'timeout' => (int) ($env('MC_SMTP_TIMEOUT') ?: 15),
        ],
    ],
];
