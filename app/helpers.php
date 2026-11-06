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

/**
 * Query parameter that carries the application route, e.g. ?p=product&slug=x.
 *
 * Only a fallback: clean URLs are on by default and the paths themselves carry
 * the route. Old `?r=` URLs are still accepted and 301 to the clean path.
 */
function route_param(): string
{
    return 'p';
}

/** Parameters from earlier URL schemes that are still accepted and redirected. */
function legacy_route_params(): array
{
    return ['r'];
}

/** Every query parameter that can carry the route; the first one is canonical. */
function route_param_keys(): array
{
    return array_merge([route_param()], legacy_route_params());
}

/** Build an application URL. Route '' is the home page. */
function url(string $route = '', array $params = []): string
{
    // Query URLs are the default; enabling SEO_CLEAN_URLS emits readable
    // paths such as /product/slug and keeps the old URLs working.
    return \App\Support\Site::routePath($route, $params);
}

/**
 * URL of a static asset stored under /assets.
 *
 * Always root-relative: clean URLs put pages at /shop/{slug} and
 * /collections/{slug}, where a bare `assets/...` would resolve one directory
 * deeper and 404 (the page renders unstyled).
 */
function asset(string $path): string
{
    $path = '/assets/' . ltrim($path, '/');

    // Version by modification time so a browser that cached an old script or
    // stylesheet picks up the current one after a deploy instead of running
    // stale JavaScript against newer markup.
    $file = dirname(__DIR__) . $path;
    $mtime = is_file($file) ? filemtime($file) : false;

    return $mtime !== false ? $path . '?v=' . $mtime : $path;
}

/** Image helper that tolerates empty values; relative paths become root-relative. */
function image_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '/assets/images/placeholder.svg';
    }

    // Absolute URLs, protocol-relative URLs, data URIs and site-root paths are
    // already usable from any depth.
    if ($path[0] === '/' || str_contains($path, '://') || str_starts_with($path, 'data:')) {
        return $path;
    }

    return '/' . ltrim($path, '/');
}

function redirect(string $route = '', array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function redirect_to(string $location, int $status = 302): void
{
    header('Location: ' . $location, true, $status);
    exit;
}

/**
 * Format an amount using the configured locale and currency.
 *
 * When no currency is configured the symbol is omitted rather than guessed.
 */
function money($amount): string
{
    $currency = \App\Support\Site::currency();
    $value = (float) $amount;

    if ($currency === '') {
        return number_format($value, 2);
    }

    if (class_exists(\NumberFormatter::class)) {
        $formatter = new \NumberFormatter(\App\Support\Site::locale(), \NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($value, $currency);
        if (is_string($formatted)) {
            return $formatted;
        }
    }

    return number_format($value, 2) . ' ' . $currency;
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

/** Minimum price a product may be sold for (config app.min_product_price). */
function min_product_price(): float
{
    return (float) config('app.min_product_price');
}

/**
 * True when a product can be bought. Every priced product is sellable; the
 * stock counter is retained for the admin console but never marks a listing
 * as out of stock on the storefront.
 *
 * Imported rows without a price are treated as unavailable, so the storefront
 * never shows "$0.00" and never offers an item that cannot be charged for.
 */
function product_available(array $product): bool
{
    $price = (float) ($product['price'] ?? 0);

    return $price >= min_product_price();
}

/** True when a product carries a price that can be displayed and charged. */
function product_has_price(array $product): bool
{
    return (float) ($product['price'] ?? 0) >= min_product_price();
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
    foreach (route_param_keys() as $key) {
        if (($_GET[$key] ?? '') === $route) {
            return true;
        }
    }

    return false;
}

/**
 * Resolve the current request into [route, params].
 *
 * Query URLs (current and legacy parameters), clean paths and the crawl
 * endpoints are all accepted so existing links keep working.
 *
 * @return array{0:string,1:array<string,string>}
 */
function route_request(): array
{
    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $param = route_param();
    $route = trim((string) ($_GET[$param] ?? ''), '/');

    if ($route === '') {
        foreach (legacy_route_params() as $legacy) {
            if (isset($_GET[$legacy])) {
                $route = trim((string) $_GET[$legacy], '/');
                break;
            }
        }
    }

    if ($route !== '' || isset($_GET[$param])) {
        return [$route, []];
    }
    if ($path === '/' || $path === '/index.php') {
        return ['', []];
    }
    if ($path === '/robots.txt') {
        return ['robots', []];
    }
    if ($path === '/sitemap.xml') {
        return ['sitemap', []];
    }
    if ($path === '/feed/products.xml') {
        return ['feed', []];
    }
    if (preg_match('#^/sitemap-([a-z]+)(?:-(\d+))?\.xml$#', $path, $matches) === 1) {
        if (isset($matches[2]) && $matches[2] !== '') {
            $_GET['page'] = (int) $matches[2];
        }
        return ['sitemap-' . $matches[1], []];
    }
    if (!\App\Support\Site::get('cleanUrls', false)) {
        return ['__not_found__', []];
    }

    return \App\Support\Site::routeFromPath($path);
}

/**
 * True for hostnames that only exist on a developer machine.
 *
 * Herd and Valet serve *.test, Docker and `php -S` serve localhost/127.0.0.1.
 * Development must never be bounced to the production host, even when .env
 * still points SITE_URL at production.
 */
function is_local_host(string $host): bool
{
    $host = strtolower(trim($host));
    if ($host === '') {
        return false;
    }

    // Drop the port, and the brackets around an IPv6 literal.
    if (preg_match('/^\[(.+)\](?::\d+)?$/', $host, $matches) === 1) {
        $host = $matches[1];
    } else {
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    }

    if ($host === '' || in_array($host, ['localhost', '::1', '0.0.0.0'], true)) {
        return true;
    }

    foreach (['.test', '.local', '.localhost', '.internal', '.example'] as $suffix) {
        if (str_ends_with($host, $suffix)) {
            return true;
        }
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $long = ip2long($host);

        return $long !== false && (($long & 0xFF000000) === 0x7F000000   // 127.0.0.0/8
            || ($long & 0xFF000000) === 0x0A000000                       // 10.0.0.0/8
            || ($long & 0xFFF00000) === 0xAC100000                       // 172.16.0.0/12
            || ($long & 0xFFFF0000) === 0xC0A80000);                     // 192.168.0.0/16
    }

    return false;
}

/**
 * Redirect requests that do not use the one canonical host/protocol, and
 * normalise trailing slashes. Disabled in local environments by config, and
 * always skipped for local dev hosts (see is_local_host()).
 */
function enforce_canonical_request(): void
{
    $siteUrl = \App\Support\Site::siteUrl();
    if ($siteUrl === '' || !\App\Support\Site::get('canonicalRedirect', false)) {
        return;
    }

    $target = parse_url($siteUrl);
    if ($target === false || empty($target['host'])) {
        return;
    }

    $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

    // Local development (Herd/Valet *.test, localhost, private IPs) keeps the
    // host it was opened with; only production hosts are canonicalised.
    if (is_local_host($host)) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    $needsHost = $host !== '' && strcasecmp($host, (string) $target['host']) !== 0;
    $needsScheme = ($target['scheme'] ?? 'https') === 'https' && !$isHttps;

    $normalisedPath = $requestPath;
    if (\App\Support\Site::get('cleanUrls', false) && $requestPath !== '/') {
        $normalisedPath = rtrim($requestPath, '/');
        if ($normalisedPath === '') {
            $normalisedPath = '/';
        }
    }
    $needsSlash = $normalisedPath !== $requestPath;

    if ($needsHost || $needsScheme || $needsSlash) {
        // The preferred homepage URL is the one with the trailing slash.
        $location = $siteUrl . ($normalisedPath === '/' ? '/' : $normalisedPath)
            . ($query !== '' ? '?' . $query : '');
        header('Location: ' . $location, true, 301);
        exit;
    }
}

/**
 * Collapse trailing slashes so a clean URL has exactly one form.
 * Runs even when canonicalHost redirects are disabled (local/staging).
 */
function normalize_request_path(): void
{
    if (!\App\Support\Site::get('cleanUrls', false)) {
        return;
    }
    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if ($path === '/' || !str_ends_with($path, '/')) {
        return;
    }
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    header('Location: ' . rtrim($path, '/') . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

/**
 * 301 every non-canonical URL form to the current one.
 *
 * With clean URLs on, `index.php?p=<route>` and the older `?r=<route>` form
 * both redirect in one hop to the path the sitemap lists, and bare
 * `/index.php` redirects to `/`. Only GET requests are redirected, so form
 * posts keep working untouched.
 */
function redirect_legacy_url(): void
{
    if (request_method() !== 'GET') {
        return;
    }

    $cleanUrls = (bool) \App\Support\Site::get('cleanUrls', false);

    foreach (route_param_keys() as $key) {
        if (!array_key_exists($key, $_GET)) {
            continue;
        }

        $route = trim((string) $_GET[$key], '/');
        $params = $_GET;
        unset($params[$key]);

        if ($cleanUrls) {
            $target = \App\Support\Site::routePath($route, $params);
        } else {
            $query = route_param() . '=' . $route;
            if ($params !== []) {
                $query .= '&' . http_build_query($params);
            }
            // Root-relative, like the clean form: a bare `index.php?...`
            // Location is resolved against the request path by the browser and
            // would send it back to the same URL forever.
            $target = '/index.php' . ($query !== '' ? '?' . $query : '');
        }

        $current = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')
            . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');

        // Compare as written by the browser: both sides are root-relative, so
        // drop the leading slash before comparing or the redirect loops.
        if (ltrim($target, '/') !== ltrim($current, '/')) {
            header('Location: ' . $target, true, 301);
            exit;
        }

        return;
    }

    // The front controller file itself is never a canonical URL.
    if ($cleanUrls) {
        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if ($path === '/index.php') {
            $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
            header('Location: /' . ($query !== '' ? '?' . $query : ''), true, 301);
            exit;
        }
    }
}