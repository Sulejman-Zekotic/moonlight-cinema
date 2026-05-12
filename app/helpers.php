<?php

declare(strict_types=1);

function base_path_url(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\', '/', dirname($scriptName));
    $dir = $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');

    $base = $dir;

    return $base;
}

function url_for(string $path = '/'): string
{
    $base = base_path_url();
    $trimmed = ltrim($path, '/');

    if ($trimmed === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $trimmed;
}

function asset_url(string $path): string
{
    return url_for('assets/' . ltrim($path, '/'));
}

function media_url(string $path): string
{
    return asset_url('media/' . ltrim($path, '/'));
}

function request_path(): string
{
    $uriPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $base = base_path_url();

    if ($base !== '' && str_starts_with($uriPath, $base)) {
        $uriPath = substr($uriPath, strlen($base));
    }

    $uriPath = trim($uriPath, '/');

    return $uriPath === '' ? '/' : $uriPath;
}

function redirect_to(string $path): never
{
    header('Location: ' . url_for($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function render_partial(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/views/' . $view . '.php';
}

function render_view(string $view, array $data = [], string $layout = 'main'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/views/layouts/' . $layout . '.php';
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_success(mixed $data = null): never
{
    json_response(['success' => true, 'data' => $data]);
}

function json_error(string $message, int $status = 422): never
{
    json_response(['success' => false, 'data' => $message], $status);
}

function request_value(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function is_post(): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
}

function current_full_url(string $path, array $query = []): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $url = $scheme . '://' . $host . url_for($path);

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function format_date_local(?string $date, string $pattern = 'd.m.Y'): string
{
    if (!$date) {
        return '';
    }

    return date($pattern, strtotime($date));
}

function format_time_local(?string $time): string
{
    if (!$time) {
        return '';
    }

    return substr($time, 0, 5);
}

function movie_link(int $movieId): string
{
    return url_for('film?id=' . $movieId);
}

function reservation_link(int $movieId): string
{
    return url_for('rezervacija?mid=' . $movieId);
}
