<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Access to the single site configuration file plus URL helpers.
 *
 * Absolute URLs are always built from the configured siteUrl, never from
 * request headers, so a spoofed Host header cannot poison canonicals,
 * sitemaps or the product feed.
 */
final class Site
{
    /** Values that mean "not supplied yet" rather than a real value. */
    private const PLACEHOLDERS = [
        '', 'todo', 'tbd', 'xxx', 'xx', 'xx-xx', 'change-me', 'changeme',
        'example.com', 'your store name', 'your-store', 'n/a', 'none',
    ];

    /** @var array<string,mixed>|null */
    private static ?array $config = null;

    /** @return array<string,mixed> */
    public static function config(): array
    {
        if (self::$config === null) {
            self::$config = require dirname(__DIR__, 2) . '/config/site.php';
        }

        return self::$config;
    }

    /** A configured value, or the default when it has not been supplied. */
    public static function get(string $key, $default = null)
    {
        $config = self::config();

        return array_key_exists($key, $config) ? $config[$key] : $default;
    }

    /** True only when a value is real, not an empty string or a placeholder. */
    public static function filled($value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!self::filled($item)) {
                    return false;
                }
            }

            return $value !== [];
        }
        if (!is_scalar($value)) {
            return false;
        }

        $normalised = strtolower(trim((string) $value));

        return $normalised !== '' && !in_array($normalised, self::PLACEHOLDERS, true);
    }

    /** Store name used in titles and schema, or an empty string when unset. */
    public static function name(): string
    {
        return self::filled(self::get('storeName')) ? trim((string) self::get('storeName')) : '';
    }

    /** Configured site root without a trailing slash, or '' when unset. */
    public static function siteUrl(): string
    {
        $url = trim((string) self::get('siteUrl'));

        return self::filled($url) ? rtrim($url, '/') : '';
    }

    /** Absolute URL for a path relative to the site root. */
    public static function absolute(string $path): string
    {
        $path = ltrim($path, '/');
        $root = self::siteUrl();

        return $root === '' ? $path : $root . '/' . $path;
    }

    /** `<html lang>` / hreflang value, e.g. en-US, or '' when unset. */
    public static function htmlLang(): string
    {
        $locale = trim((string) self::get('locale'));
        $language = trim((string) self::get('language'));

        if (self::filled($locale)) {
            return str_replace('_', '-', $locale);
        }

        return self::filled($language) ? $language : '';
    }

    /** Locale used for number/date formatting, falling back to en. */
    public static function locale(): string
    {
        $locale = trim((string) self::get('locale'));

        return self::filled($locale) ? str_replace('_', '-', $locale) : 'en';
    }

    /** ISO 4217 currency code, or '' when unset. */
    public static function currency(): string
    {
        $currency = strtoupper(trim((string) self::get('currency')));

        return self::filled($currency) ? $currency : '';
    }

    /**
     * Path (relative to the host) for an application route.
     *
     * When clean URLs are enabled the readable form is emitted; otherwise the
     * legacy `index.php?r=` form is preserved so existing links keep working.
     */
    public static function routePath(string $route = '', array $params = []): string
    {
        if (!self::get('cleanUrls')) {
            // Keep route slashes readable (r=cart/add, not r=cart%2Fadd).
            $query = $route !== '' ? 'r=' . $route : '';
            if ($params !== []) {
                $query .= ($query === '' ? '' : '&') . http_build_query($params);
            }

            return 'index.php' . ($query !== '' ? '?' . $query : '');
        }

        $segments = match ($route) {
            '' => [],
            'all' => ['products'],
            'search' => ['search'],
            'sale' => ['sale'],
            'faq' => ['faq'],
            'articles' => ['articles'],
            'events' => ['events'],
            'contact' => ['contact'],
            'article' => ['articles', (string) ($params['slug'] ?? '')],
            'page' => ['pages', (string) ($params['slug'] ?? '')],
            'product' => ['product', (string) ($params['slug'] ?? '')],
            'category' => ['category', (string) ($params['slug'] ?? '')],
            'brand' => ['brand', (string) ($params['slug'] ?? '')],
            'goal' => ['goal', (string) ($params['slug'] ?? '')],
            'shipping' => ['shipping', (string) ($params['slug'] ?? '')],
            'order' => ['orders', (string) ($params['number'] ?? '')],
            'account/order' => ['account', 'orders', (string) ($params['number'] ?? '')],
            default => array_values(array_filter(explode('/', $route), static fn ($part) => $part !== '')),
        };

        if (in_array($route, ['product', 'category', 'brand', 'goal', 'shipping'], true)) {
            unset($params['slug']);
        }
        if ($route === 'order') {
            unset($params['number']);
        }
        if ($route === 'account/order') {
            unset($params['number']);
        }

        $path = '/' . implode('/', array_map('rawurlencode', $segments));
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $query = http_build_query($params);

        return $path . ($query !== '' ? '?' . $query : '');
    }

    /** Absolute URL for an application route. */
    public static function absoluteRoute(string $route = '', array $params = []): string
    {
        $path = self::routePath($route, $params);

        if ($path === 'index.php') {
            return self::absolute('');
        }

        return self::absolute($path);
    }

    /**
     * Convert a clean path into [route, params].
     *
     * @return array{0:string,1:array<string,string>}
     */
    public static function routeFromPath(string $path): array
    {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            return ['', []];
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($s) => $s !== ''));
        $segments = array_map('rawurldecode', $segments);
        $first = $segments[0] ?? '';
        $second = $segments[1] ?? '';

        if ($first === 'products') {
            return ['all', []];
        }
        if ($first === 'articles' && $second !== '') {
            return ['article', ['slug' => $second]];
        }
        if ($first === 'pages' && $second !== '') {
            return ['page', ['slug' => $second]];
        }
        if ($first === 'product' && $second !== '') {
            return ['product', ['slug' => $second]];
        }
        if ($first === 'category' && $second !== '') {
            return ['category', ['slug' => $second]];
        }
        if ($first === 'brand' && $second !== '') {
            return ['brand', ['slug' => $second]];
        }
        if ($first === 'goal' && $second !== '') {
            return ['goal', ['slug' => $second]];
        }
        if ($first === 'shipping' && $second !== '') {
            return ['shipping', ['slug' => $second]];
        }
        if ($first === 'orders' && $second !== '') {
            return ['order', ['number' => $second]];
        }
        if ($first === 'account' && $second === 'orders' && isset($segments[2])) {
            return ['account/order', ['number' => $segments[2]]];
        }

        $allowed = ['search', 'sale', 'faq', 'articles', 'events', 'contact', 'cart',
            'checkout', 'login', 'register', 'logout', 'forgot', 'reset', 'account',
            'newsletter', 'review', 'admin'];
        if (!in_array($first, $allowed, true)) {
            return ['__not_found__', []];
        }

        return [implode('/', $segments), []];
    }

    /** Routes that must never be indexed. */
    public static function isPrivateRoute(string $route): bool
    {
        foreach (['cart', 'checkout', 'order', 'login', 'register', 'logout', 'forgot',
            'reset', 'account', 'admin', 'newsletter', 'review'] as $prefix) {
            if ($route === $prefix || str_starts_with($route, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Config fields that are still empty and the features they disable.
     *
     * @return array<int,array{field:string,label:string,disabled:string}>
     */
    public static function gaps(): array
    {
        $rows = [];
        $check = static function (string $field, string $label, bool $ok, string $disabled) use (&$rows): void {
            if (!$ok) {
                $rows[] = ['field' => $field, 'label' => $label, 'disabled' => $disabled];
            }
        };

        $config = self::config();
        $check('siteUrl', 'SITE_URL / APP_URL', self::siteUrl() !== '', 'Absolute canonicals, sitemaps, robots.txt sitemap reference, product feed');
        $check('storeName', 'SITE_STORE_NAME', self::name() !== '', 'Store name in titles, structured data, footer');
        $check('legalName', 'SITE_LEGAL_NAME', self::filled($config['legalName']), 'Organization legal name');
        $check('tagline', 'SITE_TAGLINE', self::filled($config['tagline']), 'Home page tagline');
        $check('country', 'SITE_COUNTRY', self::filled($config['country']), 'Country in titles/descriptions, target market, Merchant Center');
        $check('locale', 'SITE_LOCALE', self::filled($config['locale']), 'html lang, hreflang, number formatting');
        $check('language', 'SITE_LANGUAGE', self::filled($config['language']), 'html lang fallback');
        $check('currency', 'APP_CURRENCY', self::currency() !== '', 'Prices, schema priceCurrency, feed currency');
        $check('address', 'SITE_ADDRESS_STREET + SITE_CITY + SITE_ADDRESS_POSTAL', self::filled($config['address']), 'Pharmacy / LocalBusiness schema, location page');
        $check('phone', 'SITE_PHONE', self::filled($config['phone']), 'Pharmacy schema telephone, contact details');
        $check('openingHours', 'SITE_OPENING_HOURS', self::filled($config['openingHours']), 'Pharmacy opening hours');
        $check('email', 'SITE_EMAIL', self::filled($config['email']), 'Organization email, contact page');
        $check('logoPath', 'SITE_LOGO_PATH', self::filled($config['logoPath']), 'Organization logo, Open Graph fallback');
        $check('defaultOgImage', 'SITE_OG_IMAGE', self::filled($config['defaultOgImage']), 'Default Open Graph image');
        $check('pharmacyLicenceNumber', 'REG_STATE_LICENCE', self::filled($config['regulatory']['pharmacyLicenceNumber'] ?? ''), 'Licence block on the page and in structured data');
        $check('licensingAuthorityName', 'REG_STATE_BOARD', self::filled($config['regulatory']['licensingAuthorityName'] ?? ''), 'Licensing authority line');
        $check('superintendentPharmacistName', 'REG_PIC_NAME', self::filled($config['regulatory']['superintendentPharmacistName'] ?? ''), 'Responsible pharmacist byline');
        $check('medicinesRegulatorName', 'REG_MEDICINES_REGULATOR', self::filled($config['regulatory']['medicinesRegulatorName'] ?? ''), 'Neutral regulatory wording in policy pages');
        $check('dataProtectionLawName', 'REG_DATA_PROTECTION_LAW', self::filled($config['regulatory']['dataProtectionLawName'] ?? ''), 'Privacy policy legal reference');
        $check('gscVerification', 'GSC_VERIFICATION', self::filled($config['gscVerification']), 'Search Console verification meta tag');
        $check('merchantCountry', 'SEO_MERCHANT_COUNTRY', self::filled($config['merchantCountry']), 'Google Merchant Center feed target country');
        $check('merchantFeed', 'SEO_MERCHANT_FEED=true', (bool) $config['merchantFeed'], 'Product feed endpoint (404 until enabled)');

        return $rows;
    }
}
