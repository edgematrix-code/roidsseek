<?php

declare(strict_types=1);

use App\Support\Auth;
use App\Support\Database;

/** Read an environment variable, coercing common literal strings to their PHP types. */
function env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    if ($value === null) {
        return $default;
    }

    return match (strtolower(trim((string) $value))) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => (string) $value,
    };
}

/**
 * Load key=value pairs from a .env file into the process environment.
 *
 * Values that already exist in the real environment win, so deployments can
 * override the file without editing it.
 */
function load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '') {
            continue;
        }

        $value = trim($value);
        $length = strlen($value);
        if ($length > 1 && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

/** Read a configuration value using dot notation: config('db.host'). */
function config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/config.php';
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/** Escape output for HTML contexts. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an application URL. Route '' is the home page. */
function url(string $route = '', array $params = []): string
{
    // The route segment is built by hand so slashes stay readable
    // (r=admin/product instead of r=admin%2Fproduct).
    $query = '';
    if ($route !== '') {
        $query = 'r=' . $route;
    }
    if ($params !== []) {
        $query .= ($query === '' ? '' : '&') . http_build_query($params);
    }

    return 'index.php' . ($query !== '' ? '?' . $query : '');
}

/** URL of a static asset stored under /assets. */
function asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

/** Image helper that tolerates empty values. */
function image_url(?string $path): string
{
    $path = trim((string) $path);

    return $path !== '' ? $path : 'assets/images/placeholder.svg';
}

function redirect(string $route = '', array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function redirect_to(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function money($amount): string
{
    return '$' . number_format((float) $amount, 2);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $value;
}

function has_flash(string $key): bool
{
    return isset($_SESSION['flash'][$key]);
}

function old(string $key, $default = '')
{
    $value = $_SESSION['_old'][$key] ?? $default;

    return $value;
}

function remember_old(array $data): void
{
    unset($data['password'], $data['password_confirmation'], $data['_token']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        flash('error', 'Your session expired. Please try again.');
        redirect('');
    }
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function input(string $key, $default = null)
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

function query_int(string $key, int $default = 0): int
{
    return (int) ($_GET[$key] ?? $default);
}

function user(): ?array
{
    return Auth::user();
}

function is_logged_in(): bool
{
    return Auth::check();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

function old_price_active(array $product): bool
{
    return !empty($product['old_price']) && (float) $product['old_price'] > (float) $product['price'];
}

/** Current cart item count, safe to call in views. */
function cart_count(): int
{
    return \App\Support\Cart::count();
}

function stars(float $rating): string
{
    $full = (int) round($rating);
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $full
            ? '<span class="star filled">&#9733;</span>'
            : '<span class="star">&#9733;</span>';
    }

    return $out;
}

/** Simple paginator metadata. */
function paginate(int $total, int $perPage, int $current): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $current = max(1, min($current, $pages));

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current' => $current,
        'pages' => $pages,
        'from' => $total === 0 ? 0 : (($current - 1) * $perPage) + 1,
        'to' => min($total, $current * $perPage),
        'offset' => ($current - 1) * $perPage,
    ];
}

/** Build a route URL preserving current filters. An empty value removes the key. */
function filter_url(string $route, array $params, string $key, $value): string
{
    if ($value === null || $value === '') {
        unset($params[$key]);
    } else {
        $params[$key] = $value;
    }
    unset($params['page']);

    return url($route, $params);
}

function is_active_path(string $route, array $params = []): bool
{
    return ($_GET['r'] ?? '') === $route;
}
