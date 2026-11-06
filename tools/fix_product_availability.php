<?php

/**
 * One-off availability correction.
 *
 *   php tools/fix_product_availability.php --dry-run   # report only
 *   php tools/fix_product_availability.php             # apply
 *
 * Imported catalogue rows without a price cannot be charged for, so they are
 * taken out of stock and the storefront hides their $0.00 price. Products that
 * do carry a price are put back in stock so they are orderable again.
 *
 * Run this once per database. Re-running it will restock any priced product
 * whose stock was deliberately set to 0, so do not keep it in a deploy script.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\Database;

$dryRun = in_array('--dry-run', $argv ?? [], true);

$unpriced = Database::all('SELECT id, name, stock FROM products WHERE price <= 0 AND stock <> 0 ORDER BY id');
$pricedOut = Database::all('SELECT id, name, stock FROM products WHERE price > 0 AND stock <= 0 AND is_active = 1 ORDER BY id');

echo 'Unpriced products to mark out of stock: ' . count($unpriced) . "\n";
foreach (array_slice($unpriced, 0, 5) as $row) {
    echo '  - ' . $row['name'] . ' (stock ' . (int) $row['stock'] . ")\n";
}
if (count($unpriced) > 5) {
    echo '  ... and ' . (count($unpriced) - 5) . " more\n";
}

echo 'Priced products to put back in stock: ' . count($pricedOut) . "\n";
foreach ($pricedOut as $row) {
    echo '  - ' . $row['name'] . "\n";
}

if ($dryRun) {
    echo "Dry run: nothing was written.\n";
    exit(0);
}

Database::run('UPDATE products SET stock = 0 WHERE price <= 0');
Database::run('UPDATE products SET stock = 50 WHERE price > 0 AND stock <= 0 AND is_active = 1');

echo "Done: unpriced products are out of stock, priced products are available.\n";
