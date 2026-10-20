<?php

/**
 * Application configuration.
 *
 * Nothing secret lives in this file: every value is read from the environment,
 * which app/bootstrap.php fills from the project .env file (copy
 * .env.example to .env and fill it in). Real environment variables take
 * precedence over the file, so production can override it without edits.
 */
$config = [
    'app' => [
        'name' => env('APP_NAME', 'RoidsSeek'),
        'url' => (string) env('APP_URL', ''),
        'currency' => env('APP_CURRENCY', 'USD'),
        'free_shipping_threshold' => (float) env('FREE_SHIPPING_THRESHOLD', 399.00),
        'shipping_flat_rate' => (float) env('SHIPPING_FLAT_RATE', 25.00),
        'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (string) env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'roidsseek'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => (string) env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
];

// Optional legacy override; .env is the supported way to configure the app.
if (is_file(__DIR__ . '/local.php')) {
    $local = require __DIR__ . '/local.php';
    foreach ($local as $section => $values) {
        $config[$section] = array_merge($config[$section] ?? [], $values);
    }
}

return $config;
