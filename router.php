<?php

declare(strict_types=1);

/**
 * Front-controller router for PHP's built-in server.
 *
 *   php -S 127.0.0.1:8000 router.php
 *
 * Every request goes through index.php so the clean URLs (/reviews,
 * /shop/{slug}, /robots.txt, ...) behave the same as they do under Apache.
 * Existing static files (CSS, JS, images) are served directly; source
 * directories, dotfiles and internal tooling are never served.
 */

$path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$root = __DIR__;

// Never expose dotfiles (credentials, git metadata, editor config). The
// .well-known prefix is the one exception because certificate validation needs
// it.
if (preg_match('#(^|/)\.(?!well-known(?:/|$))#', $path) === 1) {
    http_response_code(404);
    return true;
}

// Application sources and local tooling are not public. index.php already
// refuses these paths, but blocking them at the router keeps the built-in
// server from ever executing or streaming them.
$blocked = ['app', 'config', 'database', 'tests', 'tools', 'legacy', 'tmp', 'correct-ui'];
$firstSegment = strtolower((string) (explode('/', trim($path, '/'))[0] ?? ''));
if ($firstSegment !== '' && in_array($firstSegment, $blocked, true)) {
    http_response_code(404);
    return true;
}

// /admin.php is the documented second entry point.
if ($path === '/admin.php') {
    $_GET['p'] = 'admin';
    $_SERVER['SCRIPT_NAME'] = '/admin.php';
    require $root . '/index.php';
    return true;
}

$file = realpath($root . $path);
$insideRoot = $file !== false && strncmp($file, realpath($root) . DIRECTORY_SEPARATOR, strlen(realpath($root) . DIRECTORY_SEPARATOR)) === 0;

if ($insideRoot && is_file($file)) {
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
        // Only the front controllers are executable; any other PHP file is
        // internal and must not be requested directly.
        if (basename($file) === 'index.php') {
            require $file;
            return true;
        }
        http_response_code(404);
        return true;
    }

    // Static asset: let the built-in server stream it.
    return false;
}

require $root . '/index.php';
return true;
