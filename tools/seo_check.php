<?php

/**
 * SEO verification for a running storefront.
 *
 *   php tools/seo_check.php --base=http://127.0.0.1:8000
 *   php tools/seo_check.php --gaps        # data gap report only
 *   npm run seo:check
 *
 * Exits non-zero when a blocking SEO problem is found, so CI can gate on it.
 * Warnings (thin copy, missing optional identifiers) do not fail the run.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\Database;
use App\Support\Site;

$options = getopt('', ['base::', 'gaps', 'no-http', 'max-links::']);
$base = rtrim((string) ($options['base'] ?? getenv('SEO_CHECK_BASE') ?: 'http://127.0.0.1:8000'), '/');
$http = !isset($options['no-http']);
$maxLinks = (int) ($options['max-links'] ?? 40);

$failures = [];
$warnings = [];
$fail = static function (string $message) use (&$failures): void { $failures[] = $message; };
$warn = static function (string $message) use (&$warnings): void { $warnings[] = $message; };

// --------------------------------------------------------------------- data
$products = Database::all(
    'SELECT p.*, b.name AS brand_name FROM products p
     LEFT JOIN brands b ON b.id = p.brand_id
     WHERE p.is_active = 1 ORDER BY p.id'
);
$missingDescription = [];
$missingImage = [];
$missingAlt = [];
$unknownRx = [];
$missingIdentifier = [];
$thumbnailOnly = [];
$duplicateNames = [];

foreach ($products as $product) {
    $description = trim((string) ($product['description'] ?? ''));
    if ($description === '') {
        $missingDescription[] = $product['slug'];
    } elseif (str_word_count(strip_tags($description)) < 80) {
        $thumbnailOnly[] = $product['slug'];
    }
    $image = trim((string) ($product['image'] ?? ''));
    if ($image === '' || !is_file(APP_ROOT . '/' . ltrim($image, '/'))) {
        $missingImage[] = $product['slug'];
    }
    if (trim((string) ($product['image_alt'] ?? '')) === '') {
        $missingAlt[] = $product['slug'];
    }
    if (!in_array((string) $product['rx_status'], ['otc', 'rx'], true)) {
        $unknownRx[] = $product['slug'];
    }
    if (trim((string) ($product['registration_number'] ?? '')) === '') {
        $missingIdentifier[] = $product['slug'];
    }
    $title = \App\Support\Seo::productTitle($product, !empty($product['is_restricted']));
    $duplicateNames[mb_strtolower($title)] = ($duplicateNames[mb_strtolower($title)] ?? 0) + 1;
}
$duplicateNames = array_filter($duplicateNames, static fn (int $count): bool => $count > 1);

$categoriesWithoutCopy = Database::all(
    "SELECT slug FROM categories WHERE description IS NULL OR TRIM(description) = ''"
);

$todoLeaks = [];
foreach (Database::all('SELECT slug, meta_title, meta_description FROM products') as $row) {
    foreach (['meta_title', 'meta_description'] as $field) {
        if (stripos((string) $row[$field], 'TODO') !== false) {
            $todoLeaks[] = 'products.' . $field . ' (' . $row['slug'] . ')';
        }
    }
}
foreach (Database::all('SELECT slug, content FROM pages') as $row) {
    if (stripos((string) $row['content'], 'TODO') !== false) {
        $todoLeaks[] = 'pages.content (' . $row['slug'] . ')';
    }
}

if (Site::siteUrl() === '') {
    $fail('SITE_URL / APP_URL is not configured: canonical URLs and sitemaps are invalid.');
}
if (Site::currency() === '') {
    $fail('APP_CURRENCY is not configured: prices and schema priceCurrency cannot be emitted.');
}

if ($todoLeaks !== []) {
    $fail('Literal "TODO" found in stored content: ' . implode(', ', array_slice($todoLeaks, 0, 5)));
}

// ---------------------------------------------------------------- HTTP tools
$fetch = static function (string $url) use ($http): ?array {
    if (!$http) {
        return null;
    }
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'seo-check/1.0',
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        return is_string($body) ? ['status' => $status, 'body' => $body] : null;
    }
    $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true, 'user_agent' => 'seo-check/1.0']]);
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
            $status = (int) $matches[1];
        }
    }
    return is_string($body) ? ['status' => $status, 'body' => $body] : null;
};

$dom = static function (string $html): ?DOMDocument {
    if (trim($html) === '') {
        return null;
    }
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return $loaded ? $document : null;
};

$text = static function (?DOMDocument $document, string $tag): string {
    if (!$document) {
        return '';
    }
    $nodes = $document->getElementsByTagName($tag);
    return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : '';
};

$meta = static function (?DOMDocument $document, string $name): string {
    if (!$document) {
        return '';
    }
    foreach ($document->getElementsByTagName('meta') as $node) {
        if ($node->getAttribute('name') === $name || $node->getAttribute('property') === $name) {
            return trim($node->getAttribute('content'));
        }
    }
    return '';
};

// ------------------------------------------------------------ rendered pages
$firstProduct = $products[0]['slug'] ?? '';
$firstCategory = (string) Database::value('SELECT slug FROM categories ORDER BY sort_order LIMIT 1');
$firstArticle = (string) Database::value('SELECT slug FROM articles WHERE is_published = 1 LIMIT 1');
$routePath = static fn (string $route, array $params = []): string => Site::routePath($route, $params);
$pagesToCheck = [
    'home' => '/',
    'category' => '/' . ltrim($routePath('category', ['slug' => $firstCategory]), '/'),
    'product' => '/' . ltrim($routePath('product', ['slug' => $firstProduct]), '/'),
    'article' => '/' . ltrim($routePath('article', ['slug' => $firstArticle]), '/'),
    'trust page' => '/' . ltrim($routePath('page', ['slug' => 'medical-disclaimer']), '/'),
];

$titleSeen = [];
$internalLinks = [];
$productSchema = null;

foreach ($pagesToCheck as $label => $path) {
    $url = $base . ($path === '/' ? '/' : $path);
    $response = $fetch($url);
    if ($response === null) {
        $warn('HTTP checks skipped for ' . $label . ' (no response from ' . $url . ').');
        continue;
    }
    if ($response['status'] !== 200) {
        $fail($label . ' returned HTTP ' . $response['status'] . ' at ' . $url);
        continue;
    }
    $html = $response['body'];
    if (stripos($html, 'TODO') !== false) {
        $fail('Literal "TODO" in rendered HTML at ' . $url);
    }
    $document = $dom($html);
    $title = $text($document, 'title');
    $description = $meta($document, 'description');
    $robots = $meta($document, 'robots');
    $canonical = '';
    if ($document) {
        foreach ($document->getElementsByTagName('link') as $node) {
            if ($node->getAttribute('rel') === 'canonical') {
                $canonical = trim($node->getAttribute('href'));
            }
        }
    }

    if ($title === '') {
        $fail($label . ' has no <title>.');
    } else {
        $titleSeen[mb_strtolower($title)] = ($titleSeen[mb_strtolower($title)] ?? 0) + 1;
        if (mb_strlen($title) > 70) {
            $warn($label . ' title is ' . mb_strlen($title) . ' characters (aim for 50-60).');
        }
    }
    if ($description === '') {
        $warn($label . ' has no meta description.');
    } elseif (mb_strlen($description) > 170) {
        $warn($label . ' meta description is ' . mb_strlen($description) . ' characters (aim for 140-160).');
    }
    if ($robots === '') {
        $warn($label . ' has no robots meta tag.');
    }
    if ($canonical === '') {
        $fail($label . ' has no canonical URL.');
    } elseif (Site::siteUrl() !== '' && !str_starts_with($canonical, Site::siteUrl())) {
        $fail($label . ' canonical is not absolute on the configured host: ' . $canonical);
    }

    $h1s = $document ? $document->getElementsByTagName('h1') : null;
    if ($h1s !== null && $h1s->length !== 1) {
        $fail($label . ' has ' . $h1s->length . ' <h1> elements (expected exactly 1).');
    }

    if ($document) {
        foreach ($document->getElementsByTagName('script') as $node) {
            if ($node->getAttribute('type') !== 'application/ld+json') {
                continue;
            }
            $decoded = json_decode(trim($node->textContent), true);
            if (!is_array($decoded)) {
                $fail('JSON-LD does not parse on ' . $label . ' (' . json_last_error_msg() . ').');
                continue;
            }
            foreach ((array) ($decoded['@graph'] ?? [$decoded]) as $entry) {
                if (($entry['@type'] ?? '') === 'Product' && $label === 'product') {
                    $productSchema = $entry;
                }
            }
        }
        foreach ($document->getElementsByTagName('a') as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:')
                || str_starts_with($href, 'tel:') || str_starts_with($href, 'bitcoin:')
                || preg_match('#^https?://#', $href) === 1) {
                continue;
            }
            $internalLinks[rawurldecode($href)] = true;
        }
    }
}

if ($productSchema !== null && $products !== []) {
    $product = $products[0];
    $offers = $productSchema['offers'] ?? [];
    if (is_array($offers) && isset($offers['price'])) {
        if (abs((float) $offers['price'] - (float) $product['price']) > 0.009) {
            $fail('Product schema price does not match the database price for ' . $product['slug'] . '.');
        }
        $expected = (int) $product['stock'] > 0 ? 'InStock' : 'OutOfStock';
        if (!str_contains((string) ($offers['availability'] ?? ''), $expected)) {
            $fail('Product schema availability does not match stock for ' . $product['slug'] . '.');
        }
    } else {
        $warn('Product page has no Offer schema (price or currency may be unset).');
    }
}

foreach ($titleSeen as $title => $count) {
    if ($count > 1) {
        $fail('Duplicate <title> across checked pages: ' . $title);
    }
}

// ------------------------------------------------------------------ sitemaps
if ($http) {
    $sitemap = $fetch($base . '/sitemap.xml');
    if ($sitemap === null || $sitemap['status'] !== 200) {
        $fail('/sitemap.xml is not reachable.');
    } elseif ($sitemap !== null && !str_contains($sitemap['body'], '<sitemapindex')) {
        $fail('/sitemap.xml is not a sitemap index.');
    }

    $restricted = Database::all('SELECT slug FROM products WHERE is_restricted = 1');
    if ($restricted !== []) {
        $productsSitemap = $fetch($base . '/sitemap-products.xml');
        if ($productsSitemap !== null) {
            foreach ($restricted as $row) {
                if (str_contains($productsSitemap['body'], (string) $row['slug'])) {
                    $fail('Restricted product appears in the sitemap: ' . $row['slug']);
                }
            }
        }
    }

    $feed = $fetch($base . '/feed/products.xml');
    if ($feed !== null && $feed['status'] === 200) {
        foreach ($restricted as $row) {
            if (str_contains($feed['body'], (string) $row['slug'])) {
                $fail('Restricted product appears in the feed: ' . $row['slug']);
            }
        }
        foreach (Database::all("SELECT slug FROM products WHERE rx_status = 'rx'") as $row) {
            if (str_contains($feed['body'], (string) $row['slug'])) {
                $fail('Prescription-only product appears in the feed: ' . $row['slug']);
            }
        }
    }
}

// --------------------------------------------------------------- link checks
if ($http && $internalLinks !== []) {
    $checked = 0;
    foreach (array_keys($internalLinks) as $href) {
        if ($checked >= $maxLinks) {
            break;
        }
        if (!str_starts_with($href, '/')) {
            continue;
        }
        $response = $fetch($base . $href);
        $checked++;
        if ($response === null) {
            continue;
        }
        if ($response['status'] >= 400) {
            $fail('Broken internal link (' . $response['status'] . '): ' . $href);
        } elseif ($response['status'] === 200 && stripos($response['body'], 'name="robots" content="noindex') !== false
            && !str_contains($href, 'cart') && !str_contains($href, 'account')) {
            $warn('Indexable-looking internal link points to a noindex page: ' . $href);
        }
    }
}

// ---------------------------------------------------------------- gap report
$printGaps = static function () use (
    $products, $missingDescription, $missingImage, $missingAlt, $unknownRx,
    $missingIdentifier, $thumbnailOnly, $categoriesWithoutCopy
): void {
    echo "SEO data gap report\n";
    echo str_repeat('=', 72) . "\n";
    printf("Active products: %d\n", count($products));
    printf("  missing full description        %d\n", count($missingDescription));
    printf("  description under 80 words      %d\n", count($thumbnailOnly));
    printf("  missing or unreadable image     %d\n", count($missingImage));
    printf("  missing image alt text          %d\n", count($missingAlt));
    printf("  Rx/OTC status not classified    %d\n", count($unknownRx));
    printf("  missing registration number     %d\n", count($missingIdentifier));
    printf("Categories without copy:          %d\n", count($categoriesWithoutCopy));
    foreach ([
        'No full description' => $missingDescription,
        'Thin description' => $thumbnailOnly,
        'Missing image' => $missingImage,
        'Missing alt text' => $missingAlt,
        'Rx status unknown' => $unknownRx,
        'Missing registration number' => $missingIdentifier,
    ] as $label => $rows) {
        if ($rows !== []) {
            echo "\n" . $label . ' (first 10): ' . implode(', ', array_slice($rows, 0, 10)) . "\n";
        }
    }
    if ($categoriesWithoutCopy !== []) {
        echo "\nCategories without copy: " . implode(', ', array_column($categoriesWithoutCopy, 'slug')) . "\n";
    }
    echo "\nAll missing copy is a gap for the store owner or pharmacist. Nothing is auto-generated.\n";
};

if (isset($options['gaps'])) {
    $printGaps();
    exit(0);
}

// -------------------------------------------------------------------- output
if ($missingDescription !== []) {
    $warn(count($missingDescription) . ' active product(s) have no full description.');
}
if ($missingImage !== []) {
    $fail(count($missingImage) . ' active product(s) are missing a usable image.');
}
if ($unknownRx !== []) {
    $warn(count($unknownRx) . ' product(s) have no Rx/OTC classification.');
}
if ($duplicateNames !== []) {
    $warn(count($duplicateNames) . ' generated product title(s) are still duplicated; set a manual meta title for those products.');
}
if ($categoriesWithoutCopy !== []) {
    $warn(count($categoriesWithoutCopy) . ' category page(s) have no description.');
}

echo "SEO check against " . $base . "\n";
echo str_repeat('=', 72) . "\n";
foreach ($warnings as $warning) {
    echo "WARN  " . $warning . "\n";
}
foreach ($failures as $failure) {
    echo "FAIL  " . $failure . "\n";
}
printf("\n%d failure(s), %d warning(s).\n", count($failures), count($warnings));
if ($failures === []) {
    echo "Run `php tools/seo_check.php --gaps` for the content gap report.\n";
}
exit($failures === [] ? 0 : 1);
