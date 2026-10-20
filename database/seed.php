<?php

declare(strict_types=1);

/**
 * Creates the RoidsSeek schema and imports the catalogue.
 *
 * Usage:  php database/seed.php [--fresh]
 * The --fresh flag drops and recreates every table. Without it the script
 * refuses to run when products already exist.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Support\Database;

$fresh = in_array('--fresh', $argv ?? [], true);

// Admin credentials come from .env (see .env.example); never hardcode them.
$adminEmail = trim((string) env('ADMIN_EMAIL', 'admin@roidsseek.com'));
$adminPassword = (string) env('ADMIN_PASSWORD', '');
if ($adminEmail === '' || $adminPassword === '') {
    fwrite(STDERR, "ADMIN_EMAIL / ADMIN_PASSWORD are not set. Add them to .env before seeding.\n");
    exit(1);
}

$pdo = Database::pdo();
$existing = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'products'")->fetchColumn();

if ($existing > 0 && !$fresh) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count > 0) {
        fwrite(STDOUT, "products table already has {$count} rows. Re-run with --fresh to rebuild.\n");
        exit(0);
    }
}

// ---------------------------------------------------------------- schema
$schema = file_get_contents(__DIR__ . '/schema.sql');
$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $schema)));
foreach ($statements as $statement) {
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    $pdo->exec($statement);
}
fwrite(STDOUT, "schema created\n");

// ---------------------------------------------------------------- files
$productsData = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true, 512, JSON_THROW_ON_ERROR);
$taxonomy = json_decode(file_get_contents(__DIR__ . '/data/taxonomy.json'), true, 512, JSON_THROW_ON_ERROR);
$news = json_decode(file_get_contents(__DIR__ . '/data/news.json'), true, 512, JSON_THROW_ON_ERROR);
$faqs = json_decode(file_get_contents(__DIR__ . '/data/faqs.json'), true, 512, JSON_THROW_ON_ERROR);

$slugify = static function (string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

    return trim($text, '-');
};

// ---------------------------------------------------------------- categories / goals
$categoryIds = [];
foreach ($taxonomy['categories'] as $index => $category) {
    $categoryIds[$category['slug']] = Database::insert(
        'INSERT INTO categories (slug, name, description, sort_order) VALUES (?, ?, ?, ?)',
        [
            $category['slug'],
            $category['name'],
            'Shop ' . $category['name'] . ' at RoidsSeek with discreet worldwide shipping.',
            $index,
        ]
    );
}

$goalIds = [];
foreach ($taxonomy['goals'] as $goal) {
    $goalIds[$goal['slug']] = Database::insert(
        'INSERT INTO goals (slug, name, description) VALUES (?, ?, ?)',
        [$goal['slug'], $goal['name'], 'Products picked for ' . strtolower($goal['name']) . ' goals.']
    );
}
fwrite(STDOUT, sprintf("categories: %d, goals: %d\n", count($categoryIds), count($goalIds)));

// ---------------------------------------------------------------- brands
$canonicalBrands = [
    'kalpa-pharmaceuticals' => ['Kalpa Pharmaceuticals', ['kalpa']],
    'dragon-pharma' => ['Dragon Pharma', ['dragon pharma']],
    'axiolabs' => ['Axiolabs', ['axiolabs']],
    'british-dragon' => ['British Dragon', ['british dragon']],
    'stealth-labs' => ['Stealth Labs', ['stealth']],
    'gen-shi-labs' => ['Gen-Shi Labs', ['gen-shi', 'gen shi']],
    'generic-asia' => ['Generic Asia', ['generic asia']],
    'peptide-hubs' => ['Peptide Hubs', ['peptide hubs']],
];

$brandRows = [];
$brandIds = [];
$resolveBrand = static function (string $rawName) use (&$brandRows, &$brandIds, $canonicalBrands, $slugify): int {
    $needle = strtolower($rawName);
    foreach ($canonicalBrands as $slug => [$name, $needles]) {
        foreach ($needles as $match) {
            if (str_contains($needle, $match)) {
                if (!isset($brandIds[$slug])) {
                    $brandIds[$slug] = Database::insert(
                        'INSERT INTO brands (slug, name, description) VALUES (?, ?, ?)',
                        [$slug, $name, 'Official ' . $name . ' products stocked by RoidsSeek.']
                    );
                }

                return $brandIds[$slug];
            }
        }
    }

    $slug = $slugify($rawName);
    if (!isset($brandIds[$slug])) {
        $brandIds[$slug] = Database::insert(
            'INSERT INTO brands (slug, name, description) VALUES (?, ?, ?)',
            [$slug, $rawName, 'Products manufactured by ' . $rawName . '.']
        );
    }

    return $brandIds[$slug];
};

// ---------------------------------------------------------------- products
$usedSlugs = [];
$inserted = 0;
foreach ($productsData as $product) {
    $slug = $product['slug'] ?: $slugify($product['name']) . '-' . $product['id'];
    while (isset($usedSlugs[$slug])) {
        $slug .= '-' . $product['id'];
    }
    $usedSlugs[$slug] = true;

    $brandId = $product['brand'] !== '' ? $resolveBrand($product['brand']) : null;
    $oldPrice = $product['old_price'] !== null && $product['old_price'] > $product['price'] ? $product['old_price'] : null;

    $description = trim(sprintf(
        "%s is supplied by %s. Each pack is sealed, batch-coded and inspected before dispatch.\n\nSubstance: %s\nPack: %s\nStrength: %s\n\nOrders placed before 4pm UTC ship the same business day with discreet packaging and tracking.",
        $product['name'],
        $product['brand'] ?: 'RoidsSeek',
        $product['substance'] ?: ($product['pharmaceutical_name'] ?: 'n/a'),
        $product['pack'] ?: 'n/a',
        $product['strength'] ?: 'n/a'
    ));

    $productId = Database::insert(
        'INSERT INTO products (sku, slug, name, brand_id, substance, pharmaceutical_name, pack, strength,
            short_description, description, price, old_price, stock, image, ribbon, shipping_location,
            is_featured, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $product['sku'],
            $slug,
            $product['name'],
            $brandId,
            $product['substance'] ?: null,
            $product['pharmaceutical_name'] ?: null,
            $product['pack'] ?: null,
            $product['strength'] ?: null,
            $product['short_description'] ?: null,
            $description,
            $product['price'],
            $oldPrice,
            max(0, (int) $product['stock']),
            $product['image'] ?: null,
            $product['ribbon'] ?: null,
            $product['shipping_location'] ?: 'both',
            $inserted < 12 ? 1 : 0,
            1,
        ]
    );

    foreach ($product['categories'] as $categorySlug) {
        if (isset($categoryIds[$categorySlug])) {
            Database::run('INSERT IGNORE INTO product_category (product_id, category_id) VALUES (?, ?)', [$productId, $categoryIds[$categorySlug]]);
        }
    }
    foreach ($product['goals'] as $goalSlug) {
        if (isset($goalIds[$goalSlug])) {
            Database::run('INSERT IGNORE INTO product_goal (product_id, goal_id) VALUES (?, ?)', [$productId, $goalIds[$goalSlug]]);
        }
    }

    $inserted++;
}
fwrite(STDOUT, "products: {$inserted}\n");

// ---------------------------------------------------------------- news
foreach ($news['articles'] as $article) {
    Database::run(
        'INSERT INTO articles (slug, title, excerpt, body, image, published_at, is_published) VALUES (?, ?, ?, ?, ?, ?, 1)',
        [
            $article['slug'],
            $article['title'],
            $article['excerpt'],
            $article['body'],
            $article['image'] ?: null,
            $article['published_at'] ? date('Y-m-d', strtotime($article['published_at'])) : date('Y-m-d'),
        ]
    );
}

foreach ($news['events'] as $event) {
    Database::run(
        'INSERT INTO events (slug, title, excerpt, image, published_at) VALUES (?, ?, ?, ?, ?)',
        [
            $event['slug'],
            $event['title'],
            $event['excerpt'],
            $event['image'] ?: null,
            $event['published_at'] ? date('Y-m-d', strtotime($event['published_at'])) : null,
        ]
    );
}
fwrite(STDOUT, sprintf("articles: %d, events: %d\n", count($news['articles']), count($news['events'])));

// ---------------------------------------------------------------- faqs
foreach ($faqs as $index => $faq) {
    Database::run(
        'INSERT INTO faqs (question, answer, sort_order) VALUES (?, ?, ?)',
        [$faq['question'], $faq['answer'], $index]
    );
}
fwrite(STDOUT, 'faqs: ' . count($faqs) . "\n");

// ---------------------------------------------------------------- static pages
$staticPages = [
    'privacy-policy' => ['Privacy Policy', 'privacy-policy.html'],
    'terms-of-use' => ['Terms of Use', 'terms-of-use.html'],
    'shipping' => ['Shipping Info', 'shipping.html'],
];

foreach ($staticPages as $slug => [$title, $file]) {
    $path = is_file(APP_ROOT . '/legacy/' . $file) ? APP_ROOT . '/legacy/' . $file : APP_ROOT . '/' . $file;
    $content = '<p>Content coming soon.</p>';

    if (is_file($path)) {
        $html = file_get_contents($path);
        if (preg_match('#<main class="main">(.*)</main>#si', $html, $match)) {
            $body = $match[1];
            $body = preg_replace('#<div class="breadcrumb_container">.*?</div>\s*</div>\s*</div>#si', '', $body) ?? $body;
            $body = preg_replace('#<script\b.*?</script>#si', '', $body) ?? $body;
            $body = preg_replace('#<style\b.*?</style>#si', '', $body) ?? $body;
            $body = preg_replace('#\s(on\w+)="[^"]*"#i', '', $body) ?? $body;
            $content = trim($body);
        }
    }

    Database::run('INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)', [$slug, $title, $content]);
}

// ---------------------------------------------------------------- users
$adminId = Database::insert(
    'INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)',
    ['Store Admin', $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), '+1 555 0100', 'admin']
);

Database::insert(
    'INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)',
    ['Demo Customer', 'customer@example.com', password_hash('Customer@123', PASSWORD_DEFAULT), '+1 555 0199', 'customer']
);

// ---------------------------------------------------------------- coupons
$coupons = [
    ['WELCOME10', 'percent', 10, 0, null, null],
    ['CRYPTO15', 'percent', 15, 150, date('Y-m-d', strtotime('+90 days')), 500],
    ['FREESHIP25', 'fixed', 25, 200, null, null],
];
foreach ($coupons as [$code, $type, $value, $min, $expires, $limit]) {
    Database::run(
        'INSERT INTO coupons (code, type, value, min_subtotal, expires_at, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)',
        [$code, $type, $value, $min, $expires, $limit]
    );
}

// ---------------------------------------------------------------- reviews
$reviewProducts = Database::all('SELECT id, name FROM products ORDER BY id LIMIT 12');
$sampleReviews = [
    [5, 'Exactly as described', 'Arrived in 4 days, packaging was discreet and the tabs were sealed. Will order again.'],
    [4, 'Good value', 'Solid results at a fair price. Shipping took a little longer than expected but tracking worked.'],
    [5, 'Repeat customer', 'Third order from RoidsSeek. Never had a single issue with delivery or quality.'],
];
foreach ($reviewProducts as $index => $product) {
    [$rating, $title, $body] = $sampleReviews[$index % count($sampleReviews)];
    Database::run(
        'INSERT INTO reviews (product_id, user_id, name, rating, title, body, status) VALUES (?, ?, ?, ?, ?, ?, "approved")',
        [$product['id'], null, ['Chris M.', 'Dave R.', 'Alex T.'][$index % 3], $rating, $title, $body]
    );
}

// ---------------------------------------------------------------- demo orders
$orders = [
    ['paid', 'shipped', 1],
    ['pending', 'pending', 2],
    ['completed', 'completed', 3],
];
$customerId = (int) Database::value('SELECT id FROM users WHERE email = ?', ['customer@example.com']);
$demoProducts = Database::all('SELECT * FROM products ORDER BY id LIMIT 3');

foreach ($orders as $index => [$status, $status2, $qty]) {
    $product = $demoProducts[$index % count($demoProducts)];
    $subtotal = (float) $product['price'] * $qty;
    $shipping = $subtotal >= 399 ? 0.0 : 25.0;
    $number = 'RS-DEMO-' . ($index + 1);

    $orderId = Database::insert(
        'INSERT INTO orders (order_number, user_id, email, status, subtotal, discount, shipping_total, total,
            payment_method, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state,
            shipping_postal, shipping_country, created_at)
         VALUES (?, ?, ?, ?, ?, 0, ?, ?, "crypto", ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $number,
            $customerId,
            'customer@example.com',
            $status2,
            $subtotal,
            $shipping,
            $subtotal + $shipping,
            'Demo Customer',
            '+1 555 0199',
            '1200 Ocean Drive',
            'Miami',
            'FL',
            '33139',
            'United States',
            date('Y-m-d H:i:s', strtotime('-' . ($index + 1) . ' days')),
        ]
    );

    Database::run(
        'INSERT INTO order_items (order_id, product_id, name, sku, unit_price, qty, total) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$orderId, $product['id'], $product['name'], $product['sku'], $product['price'], $qty, $subtotal]
    );
}

fwrite(STDOUT, "users, coupons, reviews and demo orders seeded\n");
fwrite(STDOUT, "admin login: {$adminEmail} (password from ADMIN_PASSWORD in .env)\n");
fwrite(STDOUT, "customer login: customer@example.com / Customer@123\n");
