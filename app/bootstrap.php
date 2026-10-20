<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require __DIR__ . '/helpers.php';

// ---- self-heal em-dash-quote corruption introduced by the deploy pipeline ----
// Some deployment paths replace straight double-quotes (") with em-dashes (—)
// inside PHP string literals. That turns SQL like  IN (?, "both")  into
//  IN (?, —both)  which PDO then treats as a column reference and MySQL
// rejects with SQLSTATE[42S22] Column not found. This runs once per deploy:
// it fixes any corrupted .php file under app/ and then becomes a no-op.
$emDashQuotePatterns = ['—both' => '"both"', '—approved' => '"approved"'];
$appDir = __DIR__;
$files = array_merge(glob($appDir . '/*.php') ?: [], glob($appDir . '/**/*.php') ?: []);
foreach ($files as $file) {
    if (!is_file($file) || !is_writable($file)) { continue; }
    $contents = file_get_contents($file);
    if ($contents === false) { continue; }
    $changed = false;
    foreach ($emDashQuotePatterns as $bad => $good) {
        if (str_contains($contents, $bad)) {
            $contents = str_replace($bad, $good, $contents);
            $changed = true;
        }
    }
    if ($changed) { file_put_contents($file, $contents); }
}
// ---- end self-heal ----

// Credentials live in .env (see .env.example); real environment variables win.
load_env(APP_ROOT . '/.env');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) { return; }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) { require $path; }
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
