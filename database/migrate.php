<?php

/**
 * Idempotent SEO/compliance migration.
 *
 *   php database/migrate.php
 *
 * Adds the fields the SEO layer needs to an existing database. Safe to run
 * more than once: every column and index is checked before it is created.
 * Fresh installs get the same schema from database/schema.sql.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\Database;
use App\Support\Pricing;

$pdo = Database::pdo();
$database = (string) config('db.database');

/**
 * [table, column, definition]
 *
 * `is_restricted`, `rx_status`, `gtin`, `ndc` and `dosage_form` already exist
 * in some installations; they are declared here so fresh databases get them.
 */
$columns = [
    ['products', 'gtin', 'VARCHAR(40) NULL'],
    ['products', 'registration_number', 'VARCHAR(120) NULL'],
    ['products', 'active_ingredient', 'VARCHAR(190) NULL'],
    ['products', 'dosage_form', 'VARCHAR(80) NULL'],
    ['products', 'manufacturer', 'VARCHAR(190) NULL'],
    ['products', 'rx_status', "ENUM('otc','rx','unknown') NOT NULL DEFAULT 'unknown'"],
    ['products', 'is_restricted', 'TINYINT(1) NOT NULL DEFAULT 0'],
    ['products', 'ndc', 'VARCHAR(40) NULL'],
    ['products', 'discontinued', 'TINYINT(1) NOT NULL DEFAULT 0'],
    ['products', 'feed_eligible', 'TINYINT(1) NOT NULL DEFAULT 0'],
    ['products', 'drug_schema_reviewed', 'TINYINT(1) NOT NULL DEFAULT 0'],
    ['products', 'image_alt', 'VARCHAR(255) NULL'],
    ['products', 'meta_title', 'VARCHAR(190) NULL'],
    ['products', 'meta_description', 'VARCHAR(320) NULL'],
    ['categories', 'meta_title', 'VARCHAR(190) NULL'],
    ['categories', 'meta_description', 'VARCHAR(320) NULL'],
    ['brands', 'meta_title', 'VARCHAR(190) NULL'],
    ['brands', 'meta_description', 'VARCHAR(320) NULL'],
    ['goals', 'meta_title', 'VARCHAR(190) NULL'],
    ['goals', 'meta_description', 'VARCHAR(320) NULL'],
    ['articles', 'meta_title', 'VARCHAR(190) NULL'],
    ['articles', 'meta_description', 'VARCHAR(320) NULL'],
    ['articles', 'medical_reviewer_name', 'VARCHAR(190) NULL'],
    ['articles', 'medical_reviewed_at', 'DATE NULL'],
    ['articles', 'updated_at', 'DATETIME NULL'],
    ['pages', 'meta_title', 'VARCHAR(190) NULL'],
    ['pages', 'meta_description', 'VARCHAR(320) NULL'],
    ['pages', 'noindex', 'TINYINT(1) NOT NULL DEFAULT 0'],
    ['orders', 'payment_address', 'VARCHAR(255) NULL'],
    ['orders', 'payment_amount_sat', 'BIGINT UNSIGNED NOT NULL DEFAULT 0'],
    ['orders', 'payment_network', "VARCHAR(40) NOT NULL DEFAULT 'bitcoin'"],
    ['orders', 'payment_token', "VARCHAR(120) NOT NULL DEFAULT 'Bitcoin (BTC)'"],
    ['orders', 'payment_tx', 'VARCHAR(100) NULL'],
    ['orders', 'payment_submitted_at', 'DATETIME NULL'],
    ['orders', 'payment_expires_at', 'DATETIME NULL'],
    ['orders', 'payment_verified_at', 'DATETIME NULL'],
    ['orders', 'payment_overpaid_sat', 'BIGINT UNSIGNED NOT NULL DEFAULT 0'],
    // Set when an administrator has opened the row in the console. The sidebar
    // badges count only rows that are still unread.
    ['orders', 'admin_read_at', 'DATETIME NULL'],
    ['reviews', 'admin_read_at', 'DATETIME NULL'],
];

$indexes = [
    ['products', 'products_seo_index', '(is_active, is_restricted, discontinued)'],
    ['articles', 'articles_published_index', '(is_published, published_at)'],
];

$tables = [
    ['payment_log', 'CREATE TABLE IF NOT EXISTS payment_log (id INT UNSIGNED NOT NULL AUTO_INCREMENT, order_id INT UNSIGNED NOT NULL, from_status VARCHAR(40) NOT NULL, to_status VARCHAR(40) NOT NULL, actor VARCHAR(40) NOT NULL, reason VARCHAR(190) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY payment_log_order_index (order_id), CONSTRAINT payment_log_order_fk FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],
    ['payment_emails', 'CREATE TABLE IF NOT EXISTS payment_emails (id INT UNSIGNED NOT NULL AUTO_INCREMENT, order_id INT UNSIGNED NULL, recipient VARCHAR(190) NOT NULL, subject VARCHAR(255) NOT NULL, status ENUM(\'sent\', \'failed\') NOT NULL DEFAULT \'sent\', error TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY payment_emails_order_index (order_id), CONSTRAINT payment_emails_order_fk FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],
    ['rate_limiter', 'CREATE TABLE IF NOT EXISTS rate_limiter (id INT UNSIGNED NOT NULL AUTO_INCREMENT, `key` VARCHAR(190) NOT NULL, value INT NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL, PRIMARY KEY (id), UNIQUE KEY rate_limiter_key_unique (`key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'],
];

$added = 0;

foreach ($tables as [$table, $definition]) {
    $exists = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
    $exists->execute([$database, $table]);
    if ((int) $exists->fetchColumn() > 0) {
        continue;
    }
    $pdo->exec($definition);
    echo "added table {$table}\n";
    $added++;
}


$exists = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
);
$indexExists = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?'
);

// Earlier drafts used a duplicate `restricted` column. Keep the original
// `is_restricted` so the admin UI and the catalogue have one flag.
$exists->execute([$database, 'products', 'restricted']);
if ((int) $exists->fetchColumn() > 0) {
    $indexExists->execute([$database, 'products', 'products_seo_index']);
    if ((int) $indexExists->fetchColumn() > 0) {
        $pdo->exec('ALTER TABLE `products` DROP INDEX `products_seo_index`');
    }
    $pdo->exec('ALTER TABLE `products` DROP COLUMN `restricted`');
    echo "dropped duplicate products.restricted\n";
    $added++;
}

foreach ($columns as [$table, $column, $definition]) {
    $exists->execute([$database, $table, $column]);
    if ((int) $exists->fetchColumn() > 0) {
        continue;
    }
    $pdo->exec(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition));
    echo "added {$table}.{$column}\n";
    $added++;
}

// The payment state machine needs statuses older installs do not have. The
// enum is extended in place so existing 'pending' orders keep working.
$exists->execute([$database, 'orders', 'status']);
if ((int) $exists->fetchColumn() > 0) {
    $statusType = (string) Database::value(
        'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$database, 'orders', 'status']
    );
    // `processing` is the approved/being-prepared state a customer's payment
    // confirmation moves an order into.
    if (!str_contains($statusType, "'processing'")) {
        $pdo->exec(
            "ALTER TABLE `orders` MODIFY `status`
             ENUM('pending_payment','processing','payment_submitted','paid','failed','expired','pending','shipped','completed','cancelled')
             NOT NULL DEFAULT 'pending_payment'"
        );
        echo "updated orders.status enum\n";
        $added++;
    }
}

// Normalise the Rx status enum/default on installations that predate it.
$exists->execute([$database, 'products', 'rx_status']);
if ((int) $exists->fetchColumn() > 0) {
    $type = (string) Database::value(
        'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$database, 'products', 'rx_status']
    );
    if (!str_contains($type, "'unknown'")) {
        // Allow NULL for the duration of the data backfill, then tighten.
        $pdo->exec("ALTER TABLE `products` MODIFY `rx_status` ENUM('otc','rx','unknown') NULL DEFAULT 'unknown'");
        $added++;
    }
    $pdo->exec("UPDATE `products` SET `rx_status` = 'unknown' WHERE `rx_status` = '' OR `rx_status` IS NULL");
    $pdo->exec("ALTER TABLE `products` MODIFY `rx_status` ENUM('otc','rx','unknown') NOT NULL DEFAULT 'unknown'");
    echo "normalised products.rx_status\n";
}

foreach ($indexes as [$table, $name, $columnsSql]) {
    $indexExists->execute([$database, $table, $name]);
    if ((int) $indexExists->fetchColumn() > 0) {
        continue;
    }
    $pdo->exec(sprintf('ALTER TABLE `%s` ADD INDEX `%s` %s', $table, $name, $columnsSql));
    echo "added index {$table}.{$name}\n";
    $added++;
}

// Business rule: no product is sold below the configured minimum price, and the
// catalogue keeps real price variety instead of collapsing onto one repeated
// figure. The imported catalogue still holds the original prices, so rows an
// earlier floor clamp flattened are restored from it and lifted so the cheapest
// item sits exactly on the floor while the rest fan out above it. Products the
// source never priced are priced from their closest peers (same substance, then
// same brand) and imported rows left out of stock are restocked from the source,
// so the whole catalogue is sellable. Re-running changes nothing.
$minPrice = (float) config('app.min_product_price');
$catalogueFile = __DIR__ . '/data/products.json';
$catalogue = is_file($catalogueFile)
    ? (json_decode((string) file_get_contents($catalogueFile), true) ?: [])
    : [];

if ($minPrice > 0 && $catalogue !== []) {
    $bySku = [];
    $bySlug = [];
    $sourcePrices = [];
    foreach ($catalogue as $item) {
        if (!is_array($item)) {
            continue;
        }
        $sourcePrice = (float) ($item['price'] ?? 0);
        if ($sourcePrice > 0) {
            $sourcePrices[] = $sourcePrice;
        }
        if (($item['sku'] ?? '') !== '') {
            $bySku[(string) $item['sku']] = $item;
        }
        if (($item['slug'] ?? '') !== '') {
            $bySlug[(string) $item['slug']] = $item;
        }
    }
    $offset = Pricing::liftOffset($sourcePrices);

    $rows = Database::all('SELECT id, sku, slug, price, stock, substance, brand_id FROM products');
    $planned = [];
    $restock = [];
    $unpriced = [];
    $restored = 0;

    foreach ($rows as $row) {
        $item = $bySku[(string) $row['sku']] ?? $bySlug[(string) $row['slug']] ?? null;
        if ($item === null) {
            continue;
        }
        $sourcePrice = (float) ($item['price'] ?? 0);
        $currentPrice = (float) $row['price'];

        if ($sourcePrice <= 0) {
            if ($currentPrice <= 0) {
                $unpriced[] = $row;
            }
        } elseif ($sourcePrice < $minPrice && $currentPrice <= $minPrice) {
            // Still sitting on the floor from the earlier clamp: restore the
            // imported figure, lifted with the rest of the sub-floor band. The
            // cheapest item lands on the floor exactly, so compare before
            // counting to keep re-runs silent.
            $lifted = Pricing::lift($sourcePrice, $offset);
            if (abs($lifted - $currentPrice) >= 0.005) {
                $planned[(int) $row['id']] = $lifted;
                $restored++;
            }
        }
    }

    // Peer prices for the rows the source never priced.
    $knownPrices = [];
    $bySubstance = [];
    $byBrand = [];
    foreach ($rows as $row) {
        $price = $planned[(int) $row['id']] ?? (float) $row['price'];
        if ($price <= 0) {
            continue;
        }
        $knownPrices[(int) $row['id']] = $price;
        $substance = strtolower(trim((string) ($row['substance'] ?? '')));
        if ($substance !== '') {
            $bySubstance[$substance][] = $price;
        }
        $brand = (string) ($row['brand_id'] ?? '');
        if ($brand !== '') {
            $byBrand[$brand][] = $price;
        }
    }
    $catalogueMedian = Pricing::median($knownPrices);

    foreach ($unpriced as $row) {
        $substance = strtolower(trim((string) ($row['substance'] ?? '')));
        $brand = (string) ($row['brand_id'] ?? '');
        $base = Pricing::median($bySubstance[$substance] ?? []);
        if ($base <= 0) {
            $base = Pricing::median($byBrand[$brand] ?? []);
        }
        if ($base <= 0) {
            $base = $catalogueMedian;
        }
        $planned[(int) $row['id']] = Pricing::derive((string) ($row['sku'] ?: $row['slug']), $base);
    }

    // Everything with a price is in stock: imported rows that were left at zero
    // stock are restocked from the catalogue source (50 unless it says less).
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $price = $planned[$id] ?? (float) $row['price'];
        if ($price <= 0 || (int) $row['stock'] > 0) {
            continue;
        }
        $item = $bySku[(string) $row['sku']] ?? $bySlug[(string) $row['slug']] ?? null;
        if ($item !== null) {
            $restock[$id] = max(1, (int) ($item['stock'] ?? 0));
        }
    }

    $setPrice = $pdo->prepare('UPDATE `products` SET `price` = ? WHERE `id` = ?');
    foreach ($planned as $id => $price) {
        $setPrice->execute([$price, $id]);
    }

    $setStock = $pdo->prepare('UPDATE `products` SET `stock` = ? WHERE `id` = ?');
    foreach ($restock as $id => $stock) {
        $setStock->execute([$stock, $id]);
    }

    $newlyPriced = count($unpriced);
    if ($restored > 0) {
        echo "restored varied prices for {$restored} product(s) sitting on the floor\n";
        $added++;
    }
    if ($newlyPriced > 0) {
        echo "priced {$newlyPriced} previously unpriced product(s) from their closest peers\n";
        $added++;
    }
    if ($restock !== []) {
        echo 'restocked ' . count($restock) . " out-of-stock product(s)\n";
        $added++;
    }
}

// Safety net for anything that reached the catalogue outside the import (an
// admin form cannot save below the floor, but data can be pasted into SQL).
if ($minPrice > 0) {
    $raise = $pdo->prepare('UPDATE `products` SET `price` = ? WHERE `price` > 0 AND `price` < ?');
    $raise->execute([$minPrice, $minPrice]);
    $raised = $raise->rowCount();
    if ($raised > 0) {
        echo "raised {$raised} product price(s) to the " . number_format($minPrice, 2, '.', '') . " minimum\n";
        $added++;
    }
}

// The promo coupon is app configuration stored as data: create it when it is
// missing, but never overwrite a value the owner has since edited.
$promoCode = strtoupper(trim((string) config('app.promo_code')));
if ($promoCode !== '') {
    $findPromo = $pdo->prepare('SELECT `id` FROM `coupons` WHERE UPPER(`code`) = ?');
    $findPromo->execute([$promoCode]);
    if ($findPromo->fetchColumn() === false) {
        $insertPromo = $pdo->prepare(
            'INSERT INTO `coupons` (`code`, `type`, `value`, `min_subtotal`, `is_active`) VALUES (?, ?, ?, ?, 1)'
        );
        $insertPromo->execute([
            $promoCode,
            'fixed',
            (float) config('app.bulk_discount_amount'),
            (float) config('app.bulk_discount_threshold'),
        ]);
        echo "created promo coupon {$promoCode}\n";
        $added++;
    }
}

echo $added === 0 ? "Schema already up to date.\n" : "Migration complete ({$added} changes).\n";
