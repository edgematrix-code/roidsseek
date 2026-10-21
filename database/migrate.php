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
];

$indexes = [
    ['products', 'products_seo_index', '(is_active, is_restricted, discontinued)'],
    ['articles', 'articles_published_index', '(is_published, published_at)'],
];

$exists = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
);
$indexExists = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?'
);

$added = 0;

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

echo $added === 0 ? "Schema already up to date.\n" : "Migration complete ({$added} changes).\n";
