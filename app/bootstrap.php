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
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

    if (
        str_contains($forwardedProto, 'https')
        || (!empty($_SERVER['HTTP_X_ARR_SSL']))
        || ((!empty($_SERVER['HTTPS'])) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
    ) {
        $_SERVER['HTTPS'] = 'on';
    }

    $sessionPath = '/tmp/moonlight-sessions';

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
    ini_set('session.cookie_lifetime', '0');
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');

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