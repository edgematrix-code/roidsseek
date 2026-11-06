<?php
/**
 * Renders every search/social tag in <head>.
 *
 * @var array<string,mixed> $seo Normalised by Seo::page().
 */

use App\Support\JsonLd;
use App\Support\Site;

$hasSiteUrl = Site::siteUrl() !== '';
$canonical = (string) ($seo['canonical'] ?? '');
$pageTitle = (string) ($seo['title'] ?? '');
$pageDescription = (string) ($seo['description'] ?? '');
$robots = (string) ($seo['robots'] ?? 'index,follow');
$ogType = (string) ($seo['type'] ?? 'website');
$image = (string) ($seo['image'] ?? '');
$locale = (string) ($seo['locale'] ?? '');
$siteName = (string) ($seo['siteName'] ?? '');
$ogSiteName = (string) ($seo['ogSiteName'] ?? $siteName);
$viewport = (string) ($seo['viewport'] ?? '');
if ($viewport === '') {
    $viewport = 'width=device-width, initial-scale=1';
}
$keywords = (string) ($seo['keywords'] ?? '');
$distribution = (string) ($seo['distribution'] ?? '');
$classification = (string) ($seo['classification'] ?? '');
$twitterSite = (string) ($seo['twitterSite'] ?? '');
$twitterCard = (string) ($seo['twitterCard'] ?? ($image !== '' ? 'summary_large_image' : 'summary'));
$productMeta = (array) ($seo['product'] ?? []);
$gsc = (string) Site::get('gscVerification', '');

$imageWidth = 0;
$imageHeight = 0;
if ($image !== '' && Site::siteUrl() !== '') {
    $relative = str_starts_with($image, Site::siteUrl())
        ? substr($image, strlen(Site::siteUrl()))
        : $image;
    $file = APP_ROOT . '/' . ltrim((string) parse_url($relative, PHP_URL_PATH), '/');
    if (is_file($file)) {
        $size = @getimagesize($file);
        if (is_array($size)) {
            $imageWidth = (int) $size[0];
            $imageHeight = (int) $size[1];
        }
    }
}

// Google reads the site name from the WebSite node on the homepage, so it is
// emitted there only: one WebSite definition for the whole site.
$isHome = (string) ($seo['route'] ?? '') === '';

$graph = [];
if ($hasSiteUrl) {
    $graph[] = JsonLd::organization();
    if ($isHome) {
        $graph[] = JsonLd::website();
    }
    $pharmacy = JsonLd::pharmacy();
    if ($pharmacy !== null) {
        $graph[] = $pharmacy;
    }
}
foreach ((array) ($seo['jsonLd'] ?? []) as $node) {
    if (is_array($node) && $node !== []) {
        $graph[] = $node;
    }
}
$graph = array_values(array_filter($graph, static fn ($node) => is_array($node) && $node !== []));

if ($graph !== []) {
    $json = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="<?= e($viewport) ?>">
<?php if ($distribution !== ''): ?>
    <meta name="distribution" content="<?= e($distribution) ?>">
<?php endif; ?>
<?php if ($classification !== ''): ?>
    <meta name="classification" content="<?= e($classification) ?>">
<?php endif; ?>
<?php if ($keywords !== ''): ?>
    <meta name="keywords" content="<?= e($keywords) ?>">
<?php endif; ?>
<?php if ($pageTitle !== ''): ?>
    <title><?= e($pageTitle) ?></title>
<?php endif; ?>
<?php if ($pageDescription !== ''): ?>
    <meta name="description" content="<?= e($pageDescription) ?>">
<?php endif; ?>
    <meta name="robots" content="<?= e($robots) ?>">
<?php if ($canonical !== ''): ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<?php if ($canonical !== '' && $locale !== ''): ?>
    <link rel="alternate" hreflang="<?= e($locale) ?>" href="<?= e($canonical) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e($canonical) ?>">
<?php endif; ?>
<?php if ($gsc !== '' && Site::filled($gsc)): ?>
    <meta name="google-site-verification" content="<?= e($gsc) ?>">
<?php endif; ?>
<?php if ($canonical !== ''): ?>
    <meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
    <meta property="og:type" content="<?= e($ogType) ?>">
<?php if ($pageTitle !== ''): ?>
    <meta property="og:title" content="<?= e($pageTitle) ?>">
<?php endif; ?>
<?php if ($pageDescription !== ''): ?>
    <meta property="og:description" content="<?= e($pageDescription) ?>">
<?php endif; ?>
<?php if ($ogSiteName !== ''): ?>
    <meta property="og:site_name" content="<?= e($ogSiteName) ?>">
<?php endif; ?>
<?php if ($locale !== ''): ?>
    <meta property="og:locale" content="<?= e(str_replace('-', '_', $locale)) ?>">
<?php endif; ?>
<?php if ($image !== ''): ?>
    <meta property="og:image" content="<?= e($image) ?>">
<?php if ($imageWidth > 0 && $imageHeight > 0): ?>
    <meta property="og:image:width" content="<?= $imageWidth ?>">
    <meta property="og:image:height" content="<?= $imageHeight ?>">
<?php endif; ?>
    <meta property="og:image:alt" content="<?= e($pageTitle !== '' ? $pageTitle : $siteName) ?>">
<?php endif; ?>
<?php if ($ogType === 'product' && $productMeta !== []): ?>
<?php if (isset($productMeta['price'])): ?>
    <meta property="product:price:amount" content="<?= e((string) $productMeta['price']) ?>">
<?php endif; ?>
<?php if (!empty($productMeta['currency'])): ?>
    <meta property="product:price:currency" content="<?= e((string) $productMeta['currency']) ?>">
<?php endif; ?>
<?php if (!empty($productMeta['availability'])): ?>
    <meta property="product:availability" content="<?= e((string) $productMeta['availability']) ?>">
<?php endif; ?>
<?php endif; ?>
    <meta name="twitter:card" content="<?= e($twitterCard) ?>">
<?php if ($twitterSite !== ''): ?>
    <meta name="twitter:site" content="<?= e($twitterSite) ?>">
<?php endif; ?>
<?php if ($pageTitle !== ''): ?>
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
<?php endif; ?>
<?php if ($pageDescription !== ''): ?>
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
<?php endif; ?>
<?php if ($image !== ''): ?>
    <meta name="twitter:image" content="<?= e($image) ?>">
<?php endif; ?>
<?php if ($graph !== [] && isset($json)): ?>
    <script type="application/ld+json"><?= $json ?></script>
<?php endif; ?>
