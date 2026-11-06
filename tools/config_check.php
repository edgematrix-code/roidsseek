<?php

/**
 * Lists every site-configuration field that is still unset and the features
 * that stay disabled until it is filled.
 *
 *   php tools/config_check.php
 *   npm run config:check
 *   php tools/config_check.php --production   # exits 1 when siteUrl is missing
 *
 * Reads .env only; it does not connect to the database.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/helpers.php';
load_env(APP_ROOT . '/.env');
require APP_ROOT . '/app/Support/Site.php';

use App\Support\Site;

$production = in_array('--production', $argv ?? [], true) || strtolower((string) env('APP_ENV', '')) === 'production';

$gaps = Site::gaps();
$configured = [
    'Store name' => Site::name() !== '' ? Site::name() : '(unset)',
    'Site URL' => Site::siteUrl() !== '' ? Site::siteUrl() : '(unset)',
    'Language' => Site::htmlLang() !== '' ? Site::htmlLang() : '(unset)',
    'Currency' => Site::currency() !== '' ? Site::currency() : '(unset)',
    'Timezone' => Site::filled(Site::get('timezone')) ? (string) Site::get('timezone') : '(unset)',
    'Physical location' => Site::get('hasPhysicalLocation') ? 'yes' : 'no',
    'Clean URLs' => Site::get('cleanUrls') ? 'enabled (path URLs)' : 'disabled (query ?p= fallback)',
    'Merchant feed' => Site::get('merchantFeed') ? 'enabled' : 'disabled',
];

echo "Site configuration check\n";
echo str_repeat('=', 72) . "\n";
foreach ($configured as $label => $value) {
    printf("%-22s %s\n", $label, $value);
}
echo str_repeat('-', 72) . "\n";

$warnings = [];
$currency = Site::currency();
if ($currency !== '' && preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
    $warnings[] = 'Currency "' . $currency . '" is not a 3-letter ISO 4217 code.';
}
$locale = trim((string) Site::get('locale'));
if ($locale !== '' && preg_match('/^[a-z]{2,3}([_-][A-Z]{2})?$/', $locale) !== 1) {
    $warnings[] = 'Locale "' . $locale . '" does not look like xx-XX.';
}
$siteUrl = Site::siteUrl();
if ($siteUrl !== '' && !str_starts_with($siteUrl, 'https://')) {
    $warnings[] = 'siteUrl should use https:// in production.';
}
foreach ($warnings as $warning) {
    echo "WARN  " . $warning . "\n";
}

if ($gaps === []) {
    echo "All configuration fields are filled.\n";
} else {
    echo count($gaps) . " field(s) still unset. Dependent output is omitted, not printed as a placeholder.\n\n";
    foreach ($gaps as $gap) {
        echo "  [ ] " . $gap['label'] . "\n";
        echo "      disables: " . $gap['disabled'] . "\n";
    }
}

echo str_repeat('-', 72) . "\n";
if ($production && $siteUrl === '') {
    fwrite(STDERR, "FAIL  SITE_URL (or APP_URL) is required in production.\n");
    exit(1);
}
echo $production ? "OK    siteUrl is configured; production check passed.\n" : "OK    configuration check finished (non-production).\n";
