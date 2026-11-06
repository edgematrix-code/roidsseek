<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Typed builders for JSON-LD. Every property is populated from stored data or
 * site configuration; unknown values are omitted instead of guessed.
 *
 * Markup must describe visible content only, so builders are given the same
 * records the templates render.
 */
final class JsonLd
{
    /** @return array<string,mixed> */
    public static function organization(): array
    {
        $organization = [
            '@type' => 'Organization',
            '@id' => Site::absolute('#organization'),
            'name' => Site::name() !== '' ? Site::name() : (string) Site::get('legalName', ''),
            'url' => Site::siteUrl(),
        ];

        $legalName = (string) Site::get('legalName', '');
        if (Site::filled($legalName)) {
            $organization['legalName'] = $legalName;
        }
        $logo = (string) Site::get('logoPath', '');
        if (Site::filled($logo)) {
            $organization['logo'] = [
                '@type' => 'ImageObject',
                'url' => Site::absolute($logo),
            ];
        }
        $phone = (string) Site::get('phone', '');
        if (Site::filled($phone)) {
            $organization['telephone'] = $phone;
        }
        if ((bool) Site::get('hasPhysicalLocation', false)) {
            $address = self::postalAddress();
            if ($address !== null) {
                $organization['address'] = $address;
            }
        }
        $social = array_values(array_filter((array) Site::get('social', []), [Site::class, 'filled']));
        if ($social !== []) {
            $organization['sameAs'] = $social;
        }

        return array_filter($organization, static fn ($value) => $value !== '' && $value !== []);
    }

    /**
     * WebSite node. Emitted on the homepage only, so the site name Google
     * reads has exactly one source and no competing copies.
     *
     * `url` is the preferred homepage URL including the trailing slash, and
     * `alternateName` carries the bare domain (roidsseek.io for
     * www.roidsseek.io) so Google can tie the name to the address it shows.
     */
    public static function website(): array
    {
        $website = [
            '@type' => 'WebSite',
            '@id' => Site::absolute('#website'),
            'url' => Site::absolute(''),
            'name' => Site::name(),
            'inLanguage' => Site::htmlLang(),
        ];

        $host = (string) parse_url(Site::siteUrl(), PHP_URL_HOST);
        $bare = (string) preg_replace('/^www\./i', '', $host);
        if ($bare !== '' && strcasecmp($bare, $host) !== 0) {
            $website['alternateName'] = [$bare];
        }

        $searchPath = Site::routePath('search');
        $target = Site::absolute($searchPath)
            . (str_contains($searchPath, '?') ? '&q=' : '?q=')
            . '{search_term_string}';
        $website['potentialAction'] = [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $target,
            ],
            'query-input' => 'required name=search_term_string',
        ];

        return array_filter($website, static fn ($value) => $value !== '');
    }

    /**
     * Pharmacy schema, emitted only when a real, staffed location exists and
     * every required property is configured.
     *
     * @return array<string,mixed>|null
     */
    public static function pharmacy(): ?array
    {
        if (!(bool) Site::get('hasPhysicalLocation', false)) {
            return null;
        }

        $address = self::postalAddress();
        $phone = (string) Site::get('phone', '');
        $hours = (string) Site::get('openingHours', '');
        if ($address === null || !Site::filled($phone) || !Site::filled($hours)) {
            return null;
        }

        $pharmacy = [
            '@type' => 'Pharmacy',
            '@id' => Site::absolute('#pharmacy'),
            'name' => Site::name(),
            'url' => Site::siteUrl(),
            'telephone' => $phone,
            'address' => $address,
            'openingHours' => array_map('trim', explode('|', $hours)),
        ];

        $logo = (string) Site::get('logoPath', '');
        if (Site::filled($logo)) {
            $pharmacy['image'] = Site::absolute($logo);
        }
        $lat = (string) (Site::get('geo', [])['lat'] ?? '');
        $lng = (string) (Site::get('geo', [])['lng'] ?? '');
        if (Site::filled($lat) && Site::filled($lng) && is_numeric($lat) && is_numeric($lng)) {
            $pharmacy['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ];
        }

        return $pharmacy;
    }

    /**
     * @param array<int,array{name:string,url:string}> $items
     * @return array<string,mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        $elements = [];
        $position = 1;
        foreach ($items as $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * Product schema. Prescription-only, restricted and discontinued products
     * are excluded: those pages are noindex and must not be shoppable markup.
     *
     * @param array<string,mixed> $product
     * @param array<int,array<string,mixed>> $categories
     * @return array<string,mixed>|null
     */
    public static function product(array $product, array $categories = []): ?array
    {
        // Restricted, discontinued and unpriced products have no purchasable
        // offer, so they get no Product markup either.
        if (!empty($product['is_restricted']) || !empty($product['discontinued']) || (float) ($product['price'] ?? 0) <= 0) {
            return null;
        }

        $currency = Site::currency();
        if ($currency === '') {
            return null;
        }

        $description = trim((string) ($product['description'] ?? ''));
        if ($description === '') {
            $description = trim((string) ($product['short_description'] ?? ''));
        }

        $schema = [
            '@type' => 'Product',
            'name' => (string) $product['name'],
            'sku' => (string) $product['sku'],
            'url' => Site::absoluteRoute('product', ['slug' => (string) $product['slug']]),
        ];

        $image = trim((string) ($product['image'] ?? ''));
        if ($image !== '' && is_file(APP_ROOT . '/' . ltrim($image, '/'))) {
            $schema['image'] = [Site::absolute($image)];
        }
        if ($description !== '') {
            $schema['description'] = $description;
        }

        if (Site::filled($product['brand_name'] ?? '')) {
            $schema['brand'] = ['@type' => 'Brand', 'name' => (string) $product['brand_name']];
        }

        $gtin = preg_replace('/\D/', '', (string) ($product['gtin'] ?? '')) ?? '';
        if (in_array(strlen($gtin), [8, 12, 13, 14], true)) {
            $schema['gtin' . (strlen($gtin) === 14 ? '14' : (strlen($gtin) === 13 ? '13' : (strlen($gtin) === 12 ? '12' : '8')))] = $gtin;
        } elseif (Site::filled($product['registration_number'] ?? '')) {
            $schema['mpn'] = (string) $product['registration_number'];
        }

        $category = $categories[0]['name'] ?? '';
        if ($category !== '') {
            $schema['category'] = (string) $category;
        }

        $schema['offers'] = self::offer($product, $currency);

        // Drug properties only when the record has been flagged as reviewed
        // and the individual data points exist. Never inferred.
        if (!empty($product['drug_schema_reviewed'])) {
            $drug = [
                '@type' => 'Drug',
                'name' => (string) $product['name'],
            ];
            $ingredient = trim((string) ($product['active_ingredient'] ?: ($product['substance'] ?? '')));
            if (Site::filled($ingredient)) {
                $drug['activeIngredient'] = $ingredient;
            }
            if (Site::filled($product['dosage_form'] ?? '')) {
                $drug['dosageForm'] = (string) $product['dosage_form'];
            }
            if (Site::filled($product['strength'] ?? '')) {
                $drug['strength'] = (string) $product['strength'];
            }
            if (Site::filled($product['rx_status'] ?? '')) {
                $drug['prescriptionStatus'] = $product['rx_status'] === 'rx'
                    ? 'PrescriptionOnly'
                    : 'OTC';
            }
            if (Site::filled($product['manufacturer'] ?? '')) {
                $drug['manufacturer'] = ['@type' => 'Organization', 'name' => (string) $product['manufacturer']];
            }
            $schema['drug'] = $drug;
        }

        return $schema;
    }

    /**
     * @param array<string,mixed> $product
     * @return array<string,mixed>
     */
    private static function offer(array $product, string $currency): array
    {
        $available = \product_available($product);
        $url = Site::absoluteRoute('product', ['slug' => (string) $product['slug']]);

        $offer = [
            '@type' => 'Offer',
            'url' => $url,
            'price' => number_format((float) $product['price'], 2, '.', ''),
            'priceCurrency' => $currency,
            'availability' => $available
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];

        $updated = (string) ($product['updated_at'] ?? '');
        $timestamp = $updated !== '' ? strtotime($updated) : false;
        if ($timestamp !== false) {
            $offer['priceValidUntil'] = gmdate('Y-m-d', strtotime('+365 days', $timestamp));
        }

        if (Site::name() !== '') {
            $offer['seller'] = ['@type' => 'Organization', 'name' => Site::name()];
        }

        return $offer;
    }

    /**
     * @param array<int,array<string,mixed>> $products
     * @return array<string,mixed>
     */
    public static function collection(string $name, string $url, array $products, int $page = 1): array
    {
        $items = [];
        $position = 1;
        foreach ($products as $product) {
            if (!empty($product['is_restricted']) || !empty($product['discontinued'])) {
                continue;
            }
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => Site::absoluteRoute('product', ['slug' => (string) $product['slug']]),
                'name' => (string) $product['name'],
            ];
        }

        return [
            '@type' => 'CollectionPage',
            'name' => $name . ($page > 1 ? ' - Page ' . $page : ''),
            'url' => $url,
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $items,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $article
     * @return array<string,mixed>
     */
    public static function article(array $article): array
    {
        $url = Site::absoluteRoute('article', ['slug' => (string) $article['slug']]);
        $published = (string) ($article['published_at'] ?? '');

        $schema = [
            '@type' => 'Article',
            'headline' => (string) $article['title'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'url' => $url,
        ];

        if ($published !== '') {
            $schema['datePublished'] = gmdate('c', (int) strtotime($published));
            $schema['dateModified'] = gmdate('c', (int) strtotime((string) ($article['updated_at'] ?? $published)));
        }
        $excerpt = trim((string) ($article['excerpt'] ?? ''));
        if ($excerpt !== '') {
            $schema['description'] = $excerpt;
        }
        $image = trim((string) ($article['image'] ?? ''));
        if ($image !== '') {
            $schema['image'] = [Site::absolute($image)];
        }

        $editorial = (array) Site::get('editorial', []);
        $authorName = trim((string) ($editorial['authorName'] ?? ''));
        if (Site::filled($authorName)) {
            $schema['author'] = ['@type' => 'Person', 'name' => $authorName];
        } elseif (Site::name() !== '') {
            $schema['author'] = ['@type' => 'Organization', 'name' => Site::name()];
        }

        $reviewerName = trim((string) ($editorial['reviewerName'] ?? ''));
        if (Site::filled($reviewerName)) {
            $reviewer = ['@type' => 'Person', 'name' => $reviewerName];
            $credentials = trim((string) ($editorial['reviewerCredentials'] ?? ''));
            if (Site::filled($credentials)) {
                $reviewer['jobTitle'] = $credentials;
            }
            $schema['reviewedBy'] = $reviewer;
        }

        if (Site::name() !== '') {
            $schema['publisher'] = ['@type' => 'Organization', 'name' => Site::name()];
        }

        return $schema;
    }

    /** @return array<string,mixed>|null */
    private static function postalAddress(): ?array
    {
        $address = (array) Site::get('address', []);
        $street = trim((string) ($address['street'] ?? ''));
        $city = trim((string) ($address['city'] ?? ''));
        $country = trim((string) ($address['country'] ?? ''));
        if (!Site::filled($street) || !Site::filled($city) || !Site::filled($country)) {
            return null;
        }

        $schema = [
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => $city,
            'addressCountry' => $country,
        ];
        $region = trim((string) ($address['region'] ?? ''));
        if (Site::filled($region)) {
            $schema['addressRegion'] = $region;
        }
        $postal = trim((string) ($address['postalCode'] ?? ''));
        if (Site::filled($postal)) {
            $schema['postalCode'] = $postal;
        }

        return $schema;
    }
}
