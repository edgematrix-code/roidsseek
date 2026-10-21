<?php

/**
 * Router for the PHP built-in server:
 *
 *     php -S 127.0.0.1:8000 router.php
 *
 * Nothing secret may be fetchable over HTTP, so dotfiles (.env, .git) and the
 * application source directories are refused before the server looks at the
 * file system. Static assets are served by the built-in server; every other
 * request (clean URLs, robots.txt, sitemaps, the product feed) is handed to
 * index.php.
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$name = basename(rtrim($path, '/'));

$blockedDirs = '#^/(app|config|database|tools|tests|legacy)(/|$)#';
$isDotfile = $name !== '' && str_starts_with($name, '.');

if ($isDotfile || preg_match($blockedDirs, $path) === 1) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not Found\n";
    return true;
}

$isSeoEndpoint = in_array($path, ['/robots.txt', '/sitemap.xml', '/feed/products.xml'], true)
    || preg_match('#^/sitemap-[a-z]+(?:-\d+)?\.xml$#', $path) === 1;

$hasExtension = pathinfo($path, PATHINFO_EXTENSION) !== '';

if ($isSeoEndpoint || ($path !== '/' && !$hasExtension)) {
    require __DIR__ . '/index.php';
    return true;
}

return false;
