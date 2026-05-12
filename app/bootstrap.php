<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Installer.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/CinemaRepository.php';

$config = require __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    session_name('moonlight_session');
    session_start();
}

$database = new Database($config['database']);
$installer = new Installer($database, $config);
$installer->ensureReady();

$auth = new Auth($database);
$mailer = new Mailer($config['mail']);
$repository = new CinemaRepository($database, $config, $mailer);

return [
    'config' => $config,
    'db' => $database,
    'auth' => $auth,
    'mailer' => $mailer,
    'repo' => $repository,
];
