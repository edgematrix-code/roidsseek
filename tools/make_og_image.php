<?php

/**
 * Generates the default 1200x630 Open Graph image from the configured store
 * name, tagline and logo.
 *
 *   php tools/make_og_image.php
 *   npm run seo:og-image
 *
 * Requires the GD extension with PNG/WebP support. The generated file is a
 * project asset and should be committed.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\Site;

$width = 1200;
$height = 630;
$background = [0x14, 0x1a, 0x22];
$accent = [0x2f, 0x5d, 0x8c];
$foreground = [0xff, 0xff, 0xff];
$muted = [0xc3, 0xcb, 0xd4];

$image = imagecreatetruecolor($width, $height);
imagesavealpha($image, true);
imagefill($image, 0, 0, imagecolorallocate($image, ...$background));

// Subtle diagonal accent band.
$band = imagecolorallocatealpha($image, $accent[0], $accent[1], $accent[2], 55);
imagefilledpolygon($image, [760, 0, 1200, 0, 1200, 630, 620, 630], $band);

$text = static function (string $font, int $size, int $x, int $y, array $colour, string $value) use ($image, $foreground): void {
    if ($value === '') {
        return;
    }
    $allocated = imagecolorallocate($image, ...$colour);
    imagettftext($image, $size, 0, $x, $y, $allocated, $font, $value);
};

$fonts = glob(APP_ROOT . '/assets/*bold*.ttf') ?: [];
$regularFonts = glob(APP_ROOT . '/assets/*regular*.ttf') ?: [];
$bold = $fonts[0] ?? null;
$regular = $regularFonts[0] ?? $bold;

$storeName = Site::name() !== '' ? Site::name() : 'Your store';
$tagline = trim((string) Site::get('tagline', ''));
if ($tagline === '') {
    $tagline = 'Product catalogue';
}

$logoPath = trim((string) Site::get('logoPath', ''));
$logo = null;
if ($logoPath !== '') {
    $file = APP_ROOT . '/' . ltrim($logoPath, '/');
    if (is_file($file)) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $logo = match ($extension) {
            'png' => @imagecreatefrompng($file),
            'jpg', 'jpeg' => @imagecreatefromjpeg($file),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file) : null,
            default => null,
        };
        if ($logo === false) {
            $logo = null;
        }
    }
}

$x = 90;
if ($logo instanceof GdImage) {
    $logoWidth = imagesx($logo);
    $logoHeight = imagesy($logo);
    $maxWidth = 220;
    $scale = min($maxWidth / max(1, $logoWidth), 160 / max(1, $logoHeight), 1);
    $drawWidth = (int) ($logoWidth * $scale);
    $drawHeight = (int) ($logoHeight * $scale);
    imagecopyresampled($image, $logo, $x, 110, 0, 0, $drawWidth, $drawHeight, $logoWidth, $logoHeight);
    $x += $drawWidth + 34;
}

if ($bold !== null && is_file($bold)) {
    $text($bold, 60, $x, 210, $foreground, $storeName);
    $text($regular ?? $bold, 28, $x, 275, $muted, $tagline);
} else {
    // GD built-in font fallback keeps the tool usable without TTF files.
    imagestring($image, 5, $x, 180, $storeName, imagecolorallocate($image, ...$foreground));
    imagestring($image, 3, $x, 210, $tagline, imagecolorallocate($image, ...$muted));
}

imagefilledrectangle($image, 90, 430, 210, 434, imagecolorallocate($image, ...$accent));
if ($regular !== null && is_file($regular)) {
    $text($regular, 24, 90, 500, $muted, Site::htmlLang() !== '' ? Site::htmlLang() : 'Catalogue');
}

$target = APP_ROOT . '/assets/images/og-default.png';
if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0775, true);
}
imagepng($image, $target);

echo "Wrote " . $target . "\n";
echo "Set SITE_OG_IMAGE=assets/images/og-default.png in .env\n";
