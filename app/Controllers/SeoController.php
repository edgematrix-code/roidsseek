<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Database;
use App\Support\Site;

/**
 * Machine-readable SEO endpoints: robots.txt, the sitemap index and its
 * per-section sitemaps, and the Google Merchant Center feed.
 *
 * Every URL is built from the configured siteUrl, never from request headers.
 */
class SeoController
{
    private const MAX_URLS = 45000;

    /** robots.txt never blocks CSS, JS or images. */
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');

        $lines = [
            '# Generated from config/site.php - do not edit by hand.',
            'User-agent: *',
            'Allow: /',
        ];

        // Pages that return 200 with a noindex meta tag stay crawlable: a
        // Disallow rule would hide the noindex from Google. Only the admin
        // area, mutation endpoints and faceted permutations are disallowed.
        foreach (['admin', 'newsletter', 'review'] as $route) {
            $lines[] = 'Disallow: ' . Site::routePath($route);
        }
        foreach (['sort', 'brand', 'substance', 'max_price', 'stock', 'location', 'keyword'] as $param) {
            $lines[] = 'Disallow: /*?*' . $param . '=';
        }

        if (Site::siteUrl() !== '') {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . Site::absolute('sitemap.xml');
        }

        echo implode("\n", $lines) . "\n";
    }

    public function sitemapIndex(): void
    {
        $lastmods = [
            'products' => (string) Database::value(
                'SELECT MAX(updated_at) FROM products WHERE is_active = 1 AND is_restricted = 0 AND discontinued = 0'
            ),
            'articles' => (string) Database::value('SELECT MAX(published_at) FROM articles WHERE is_published = 1'),
        ];

        $sections = ['products', 'categories', 'brands', 'goals', 'articles', 'pages', 'static'];
        $entries = [];
        foreach ($sections as $section) {
            $chunks = $section === 'products' ? $this->productChunks() : 1;
            for ($page = 1; $page <= $chunks; $page++) {
                $loc = Site::absolute($this->sitemapPath($section, $page));
                $entry = '  <sitemap>' . "\n" . '    <loc>' . $this->xml($loc) . '</loc>';
                $lastmod = $lastmods[$section] ?? '';
                if ($lastmod !== '' && $lastmod !== '0') {
                    $entry .= "\n" . '    <lastmod>' . $this->xml($this->isoDate($lastmod)) . '</lastmod>';
                }
                $entry .= "\n  </sitemap>";
                $entries[] = $entry;
            }
        }

        $this->xmlResponse(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . implode("\n", $entries) . "\n"
            . '</sitemapindex>' . "\n"
        );
    }

    public function sitemap(string $section, int $page = 1): void
    {
        $urls = match ($section) {
            'products' => $this->productUrls($page),
            'categories' => $this->listingUrls('category', 'categories', 'c.sort_order'),
            'brands' => $this->listingUrls('brand', 'brands', 'b.name'),
            'goals' => $this->listingUrls('goal', 'goals', 'g.name'),
            'articles' => $this->articleUrls(),
            'pages' => $this->pageUrls(),
            'static' => $this->staticUrls(),
            default => null,
        };

        if ($urls === null) {
            $this->notFound();
            return;
        }

        $this->xmlResponse(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n"
            . implode("\n", $urls) . "\n"
            . '</urlset>' . "\n"
        );
    }

    /**
     * Google Merchant Center feed. Disabled until a target country is
     * configured and the medicines policy has been reviewed; only products
     * explicitly marked feed-eligible, OTC and unrestricted are included.
     */
    public function feed(): void
    {
        if (!(bool) Site::get('merchantFeed', false) || !Site::filled(Site::get('merchantCountry', '')) || Site::currency() === '') {
            $this->notFound();
            return;
        }

        $country = (string) Site::get('merchantCountry');
        $currency = Site::currency();
        $products = Database::all(
            "SELECT p.*, b.name AS brand_name,
                    (SELECT c.name FROM categories c
                     JOIN product_category pc ON pc.category_id = c.id
                     WHERE pc.product_id = p.id ORDER BY c.sort_order LIMIT 1) AS category_name
             FROM products p LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
               AND p.rx_status = 'otc' AND p.feed_eligible = 1
             ORDER BY p.id"
        );

        $shipping = (float) config('app.shipping_flat_rate', 0);
        $items = [];
        foreach ($products as $product) {
            $price = number_format((float) $product['price'], 2, '.', '');
            if ((float) $price <= 0) {
                continue;
            }
            $image = trim((string) $product['image']);
            if ($image === '' || !is_file(APP_ROOT . '/' . ltrim($image, '/'))) {
                continue;
            }

            $description = trim((string) ($product['description'] ?: $product['short_description']));
            if ($description === '') {
                continue;
            }

            $items[] = [
                'g:id' => (string) $product['sku'],
                'g:title' => (string) $product['name'],
                'g:description' => Site::filled($description) ? $description : (string) $product['name'],
                'g:link' => Site::absoluteRoute('product', ['slug' => (string) $product['slug']]),
                'g:image_link' => Site::absolute($image),
                'g:availability' => (int) $product['stock'] > 0 ? 'in_stock' : 'out_of_stock',
                'g:price' => $price . ' ' . $currency,
                'g:condition' => 'new',
                'g:brand' => (string) ($product['brand_name'] ?? ''),
                'g:identifier_exists' => Site::filled($product['gtin'] ?? '') ? 'yes' : 'no',
                'g:gtin' => preg_replace('/\D/', '', (string) ($product['gtin'] ?? '')) ?: '',
                'g:mpn' => (string) ($product['registration_number'] ?? ''),
                'g:product_type' => (string) ($product['category_name'] ?? ''),
                'g:shipping' => [
                    'g:country' => $country,
                    'g:price' => number_format($shipping, 2, '.', '') . ' ' . $currency,
                ],
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n"
            . '  <channel>' . "\n"
            . '    <title>' . $this->xml(Site::name() . ' product feed') . '</title>' . "\n"
            . '    <link>' . $this->xml(Site::siteUrl()) . '</link>' . "\n"
            . '    <description>Product feed for Google Merchant Center</description>' . "\n";
        foreach ($items as $item) {
            $xml .= '    <item>' . "\n";
            foreach ($item as $tag => $value) {
                if ($value === '') {
                    continue;
                }
                if ($tag === 'g:shipping') {
                    $xml .= '      <g:shipping>' . "\n";
                    foreach ($value as $shippingTag => $shippingValue) {
                        if ($shippingValue !== '') {
                            $xml .= '        <' . $shippingTag . '>' . $this->xml($shippingValue) . '</' . $shippingTag . '>' . "\n";
                        }
                    }
                    $xml .= '      </g:shipping>' . "\n";
                    continue;
                }
                $xml .= '      <' . $tag . '>' . $this->xml($value) . '</' . $tag . '>' . "\n";
            }
            $xml .= '    </item>' . "\n";
        }
        $xml .= '  </channel>' . "\n" . '</rss>' . "\n";

        $this->xmlResponse($xml);
    }

    /** @return array<int,string> */
    private function productUrls(int $page): array
    {
        $offset = max(0, ($page - 1) * self::MAX_URLS);
        $rows = Database::all(
            'SELECT slug, image, updated_at FROM products
             WHERE is_active = 1 AND is_restricted = 0 AND discontinued = 0
             ORDER BY id LIMIT ' . self::MAX_URLS . ' OFFSET ' . $offset
        );

        $urls = [];
        foreach ($rows as $row) {
            $entry = '  <url>' . "\n"
                . '    <loc>' . $this->xml(Site::absoluteRoute('product', ['slug' => (string) $row['slug']])) . '</loc>';
            $lastmod = $this->isoDate((string) $row['updated_at']);
            if ($lastmod !== '') {
                $entry .= "\n" . '    <lastmod>' . $this->xml($lastmod) . '</lastmod>';
            }
            $image = trim((string) $row['image']);
            if ($image !== '' && is_file(APP_ROOT . '/' . ltrim($image, '/'))) {
                $entry .= "\n" . '    <image:image><image:loc>' . $this->xml(Site::absolute($image)) . '</image:loc></image:image>';
            }
            $entry .= "\n" . '  </url>';
            $urls[] = $entry;
        }

        return $urls;
    }

    /** @return array<int,string> */
    private function listingUrls(string $route, string $table, string $order): array
    {
        $sql = match ($table) {
            'categories' => "SELECT t.slug FROM categories t
                WHERE EXISTS (
                    SELECT 1 FROM product_category rel JOIN products p ON p.id = rel.product_id
                    WHERE rel.category_id = t.id AND p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
                ) ORDER BY $order",
            'goals' => "SELECT t.slug FROM goals t
                WHERE EXISTS (
                    SELECT 1 FROM product_goal rel JOIN products p ON p.id = rel.product_id
                    WHERE rel.goal_id = t.id AND p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
                ) ORDER BY $order",
            default => "SELECT t.slug FROM brands t
                WHERE EXISTS (
                    SELECT 1 FROM products p
                    WHERE p.brand_id = t.id AND p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
                ) ORDER BY $order",
        };

        $urls = [];
        foreach (Database::all($sql) as $row) {
            $urls[] = '  <url><loc>' . $this->xml(Site::absoluteRoute($route, ['slug' => (string) $row['slug']])) . '</loc></url>';
        }

        return $urls;
    }

    /** @return array<int,string> */
    private function articleUrls(): array
    {
        $rows = Database::all('SELECT slug, published_at FROM articles WHERE is_published = 1 ORDER BY published_at DESC');
        $urls = [];
        foreach ($rows as $row) {
            $entry = '  <url><loc>' . $this->xml(Site::absoluteRoute('article', ['slug' => (string) $row['slug']])) . '</loc>';
            $lastmod = $this->isoDate((string) $row['published_at']);
            if ($lastmod !== '') {
                $entry .= '<lastmod>' . $this->xml($lastmod) . '</lastmod>';
            }
            $urls[] = $entry . '</url>';
        }

        return $urls;
    }

    /** @return array<int,string> */
    private function pageUrls(): array
    {
        $rows = Database::all('SELECT slug FROM pages ORDER BY id');
        $urls = [];
        foreach ($rows as $row) {
            $urls[] = '  <url><loc>' . $this->xml(Site::absoluteRoute('page', ['slug' => (string) $row['slug']])) . '</loc></url>';
        }

        return $urls;
    }

    /** @return array<int,string> */
    private function staticUrls(): array
    {
        $routes = ['', 'all', 'sale', 'articles', 'faq', 'events', 'contact'];
        $urls = [];
        foreach ($routes as $route) {
            $urls[] = '  <url><loc>' . $this->xml(Site::absoluteRoute($route)) . '</loc></url>';
        }

        return $urls;
    }

    private function productChunks(): int
    {
        $total = (int) Database::value(
            'SELECT COUNT(*) FROM products WHERE is_active = 1 AND is_restricted = 0 AND discontinued = 0'
        );

        return max(1, (int) ceil($total / self::MAX_URLS));
    }

    private function sitemapPath(string $section, int $page): string
    {
        return $section === 'products' && $page > 1
            ? 'sitemap-products-' . $page . '.xml'
            : 'sitemap-' . $section . '.xml';
    }

    private function isoDate(string $value): string
    {
        if ($value === '' || $value === '0') {
            return '';
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? '' : gmdate('c', $timestamp);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function xmlResponse(string $xml): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=1800');
        echo $xml;
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Not Found\n";
    }
}
