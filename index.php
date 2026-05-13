<?php

declare(strict_types=1);

$app = require __DIR__ . '/app/bootstrap.php';

$path = request_path();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($path === 'api') {
    require __DIR__ . '/app/routes/api.php';
    exit;
}

if ($path === 'odjava' && $method === 'POST') {
    $app['auth']->logout();
    redirect_to('/');
}

if ($path === 'karta-preuzimanje' && $method === 'GET') {
    require __DIR__ . '/app/routes/ticket-download.php';
    exit;
}

if ($path === 'otkazi-rezervaciju' && $method === 'GET') {
    require __DIR__ . '/app/routes/cancel-reservation.php';
    exit;
}

$page = match ($path) {
    '/' => 'home',
    'filmovi' => 'movies',
    'film' => 'movie',
    'rezervacija' => 'reservation',
    'prijava' => 'auth',
    'reset-lozinke' => 'reset-password',
    'moje-karte' => 'tickets',
    'admin' => 'admin-hub',
    'admin/statistika' => 'admin-stats',
    'admin/projekcije' => 'admin-screenings',
    'admin/nova-projekcija' => 'admin-add-screening',
    'admin/auto-generator' => 'admin-auto-generator',
    default => '404',
};

$pageData = require __DIR__ . '/app/routes/pages.php';
render_view($pageData['view'], $pageData['data'], $pageData['layout'] ?? 'main');
