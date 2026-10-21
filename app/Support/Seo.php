<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builds the <head> metadata for every page from the site configuration and
 * the page's own data. Values that are not configured are omitted, never
 * printed as placeholders.
 */
final class Seo
{
    /** Query parameters that create a filter/sort permutation, not a new page. */
    private const FACET_KEYS = ['brand', 'substance', 'location', 'max_price', 'stock', 'sort', 'keyword'];

    /**
     * Normalise a page's SEO definition.
     *
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public static function page(array $overrides = []): array
    {
        $route = (string) ($overrides['route'] ?? ($_GET['r'] ?? ''));
        $params = (array) ($overrides['params'] ?? []);
        $page = (int) ($overrides['page'] ?? ($params['page'] ?? 0));

        $title = trim((string) ($overrides['title'] ?? ''));
        if ($title === '') {
            $title = self::defaultTitle($route);
        }
        if ($page > 1) {
            $title .= ' - Page ' . $page;
        }

        $description = self::clip(trim((string) ($overrides['description'] ?? '')));
        if ($description === '') {
            $description = self::clip((string) Site::get('defaultMetaDescription', ''));
        }

        $canonical = (string) ($overrides['canonical'] ?? self::canonicalFor($route, $params, $page));
        if ($canonical !== '' && ((int) ($overrides['status'] ?? 200) >= 400 || str_starts_with($route, '__'))) {
            $canonical = '';
        }

        $robots = (string) ($overrides['robots'] ?? self::robotsFor($route));

        $image = (string) ($overrides['image'] ?? '');
        if ($image === '') {
            $image = (string) Site::get('defaultOgImage', '');
        }
        if ($image === '') {
            $image = (string) Site::get('logoPath', '');
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'type' => (string) ($overrides['type'] ?? 'website'),
            'image' => $image !== '' ? Site::absolute($image) : '',
            'siteName' => Site::name(),
            'locale' => Site::htmlLang(),
            'product' => (array) ($overrides['product'] ?? []),
            'jsonLd' => (array) ($overrides['jsonLd'] ?? []),
            'status' => (int) ($overrides['status'] ?? 200),
        ];
    }

    /** Trim text to a search-result friendly length without cutting a word. */
    public static function clip(string $text, int $max = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " \t\n\r\0\x0B.,;:-");
    }

    /** Append the configured store name to a page title exactly once. */
    public static function withStore(string $title): string
    {
        $store = Site::name();
        $suffix = trim((string) Site::get('titleSuffix', ''));
        if ($suffix !== '') {
            return $title . ' ' . $suffix;
        }

        return $store !== '' && !str_contains($title, $store) ? $title . ' | ' . $store : $title;
    }

    /** Neutral title for a product page; Rx products never encourage purchase. */
    public static function productTitle(array $product, bool $restricted = false): string
    {
        $base = trim((string) $product['name']);
        $strength = trim((string) ($product['strength'] ?? ''));
        if ($strength !== '' && stripos($base, $strength) === false) {
            // Skip the strength when the product name already carries that
            // number ("Dianoxyl 50" + "50 mg/tab" would read as a duplicate).
            preg_match('/\d+(?:\.\d+)?/', $strength, $number);
            $alreadyNamed = isset($number[0])
                && preg_match('/\b' . preg_quote($number[0], '/') . '\b/', $base) === 1;
            if (!$alreadyNamed) {
                $base .= ' ' . $strength;
            }
        }
        // The brand keeps titles unique when the catalogue has two products
        // with the same name and strength, and reads naturally in results.
        $brand = trim((string) ($product['brand_name'] ?? ''));
        if ($brand !== '' && stripos($base, $brand) === false) {
            $base .= ' ' . $brand;
        }
        $rx = (string) ($product['rx_status'] ?? 'unknown');

        if ($restricted || $rx === 'rx') {
            return self::withStore($base . ' - Prescription Required');
        }

        return self::withStore($base . ' - Buy Online');
    }

    /** Title for a category/brand/condition listing. */
    public static function listingTitle(string $name, string $kind = 'category'): string
    {
        if ($kind === 'goal') {
            return self::withStore($name);
        }

        return self::withStore($name . ' - Buy Online');
    }

    /** Absolute canonical for a route, including the page number when relevant. */
    public static function canonicalFor(string $route, array $params = [], int $page = 0): string
    {
        unset($params['r']);
        foreach (self::FACET_KEYS as $key) {
            unset($params[$key]);
        }

        if ($page > 1) {
            $params['page'] = $page;
        } else {
            unset($params['page']);
        }

        return Site::absoluteRoute($route, $params);
    }

    /** Robots directive for a route, including faceted permutations. */
    public static function robotsFor(string $route): string
    {
        if (Site::isPrivateRoute($route)) {
            return 'noindex,nofollow';
        }
        if ($route === 'search') {
            return 'noindex,follow';
        }
        foreach (self::FACET_KEYS as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '' && $_GET[$key] !== []) {
                return 'noindex,follow';
            }
        }

        return 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
    }

    /** Fallback title when a controller does not supply one. */
    private static function defaultTitle(string $route): string
    {
        $store = Site::name();
        $tagline = trim((string) Site::get('tagline', ''));

        if ($route === '') {
            if ($store !== '' && $tagline !== '') {
                return $store . ' - ' . $tagline;
            }
            if ($store !== '') {
                return $store;
            }

            return $tagline !== '' ? $tagline : 'Online store';
        }

        $labels = [
            'all' => 'All products',
            'sale' => 'Deals and offers',
            'articles' => 'Guides and articles',
            'article' => 'Article',
            'faq' => 'Frequently asked questions',
            'events' => 'News and events',
            'contact' => 'Contact us',
            'search' => 'Search',
        ];
        $label = $labels[$route] ?? ucfirst(str_replace(['-', '/'], ' ', $route));

        return self::withStore($label);
    }
}
