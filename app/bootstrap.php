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
    if (
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['HTTP_X_ARR_SSL']) && $_SERVER['HTTP_X_ARR_SSL'] !== '')
    ) {
        $_SERVER['HTTPS'] = 'on';
    }

    $sessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }

    if (!is_writable($sessionPath)) {
        chmod($sessionPath, 0777);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_save_path($sessionPath);
    session_name('moonlight_session');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

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
