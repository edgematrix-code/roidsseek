<?php

declare(strict_types=1);

/**
 * Shows where the live server gets the shipping numbers the product page prints.
 *
 *   php tools/threshold_check.php
 *   or open https://<host>/tools/threshold_check.php once, then delete it
 *
 * Read-only. It prints the resolved configuration, the .env entries for the four
 * shipping keys, and any hardcoded 399 left in the deployed code or seed data.
 * No other .env value and no credential is printed.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\Cart;

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$keys = [
    'FREE_SHIPPING_THRESHOLD',
    'SHIPPING_INTERNATIONAL_RATE',
    'SHIPPING_DOMESTIC_RATE',
    'SHIPPING_FLAT_RATE',
];

$out = [];
$out[] = '=== RoidsSeek shipping diagnostic ===';
$out[] = 'root: ' . APP_ROOT;
$out[] = 'php: ' . PHP_VERSION . ' sapi=' . PHP_SAPI;
$out[] = 'opcache: enable=' . (string) ini_get('opcache.enable')
    . ' revalidate_freq=' . (string) ini_get('opcache.revalidate_freq');
$out[] = '';

$envPath = APP_ROOT . '/.env';
$envLines = is_file($envPath) ? (file($envPath, FILE_IGNORE_NEW_LINES) ?: []) : [];
$out[] = '.env: ' . (is_file($envPath) ? 'found' : 'MISSING');
foreach ($keys as $key) {
    $inFile = 'not set';
    foreach ($envLines as $line) {
        $line = trim((string) $line);
        if (str_starts_with($line, $key . '=')) {
            $inFile = trim(substr($line, strlen($key) + 1), " \t\"'");
            break;
        }
    }
    $process = getenv($key);
    $out[] = sprintf(
        '  %-28s file=%-10s process=%s',
        $key,
        $inFile,
        $process === false ? 'not set' : $process
    );
}
$out[] = '';

$rates = Cart::shippingRates();
$out[] = 'Resolved config (this is what the page renders):';
$out[] = '  free_shipping_threshold     = ' . number_format((float) config('app.free_shipping_threshold'), 2, '.', ',');
$out[] = '  shipping_international_rate = ' . number_format((float) config('app.shipping_international_rate'), 2, '.', ',');
$out[] = '  shipping_domestic_rate      = ' . number_format((float) config('app.shipping_domestic_rate'), 2, '.', ',');
$out[] = '  Cart::shippingRates()       = international ' . number_format($rates['international'], 2, '.', ',')
    . ', domestic ' . number_format($rates['domestic'], 2, '.', ',')
    . ', threshold ' . number_format($rates['threshold'], 2, '.', ',');
$out[] = '';

$suspects = [];
foreach (['app', 'config', 'database/data'] as $dir) {
    $base = APP_ROOT . '/' . $dir;
    if (!is_dir($base)) {
        continue;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($files as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if (!in_array(strtolower($file->getExtension()), ['php', 'json'], true)) {
            continue;
        }
        $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES) ?: [];
        foreach ($lines as $number => $line) {
            if (preg_match('/\b399(?:\.00)?\b/', (string) $line) === 1) {
                $suspects[] = str_replace(APP_ROOT . '/', '', $file->getPathname())
                    . ':' . ($number + 1) . '  ' . trim((string) $line);
            }
        }
    }
}

$out[] = 'Hardcoded 399 left in deployed code/seed: '
    . ($suspects === [] ? 'none' : count($suspects) . ' line(s)');
foreach ($suspects as $suspect) {
    $out[] = '  ' . $suspect;
}
$out[] = '';
$out[] = 'If the resolved threshold is not 800.00, the live .env is overriding the';
$out[] = 'code default: set FREE_SHIPPING_THRESHOLD=800 there and reload PHP.';

echo implode(PHP_EOL, $out) . PHP_EOL;
