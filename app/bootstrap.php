<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require __DIR__ . '/helpers.php';

// Credentials live in .env (see .env.example); real environment variables win.
load_env(APP_ROOT . '/.env');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$debug = (bool) config('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('roidsseek_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

App\Support\Database::connect(config('db'));

// Keep the flash message queue from growing between requests.
if (!isset($_SESSION['_started'])) {
    $_SESSION['_started'] = time();
}
