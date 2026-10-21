<?php

declare(strict_types=1);

/**
 * Single source of truth for every business detail used by templates,
 * metadata, structured data, the sitemaps and the product feed.
 *
 * Nothing in this file is invented: every value is read from the environment
 * (`.env` or real environment variables). An empty value means "not supplied
 * yet" and the dependent output is omitted rather than printed publicly.
 * Run `php tools/config_check.php` to see what is still missing.
 *
 * @phpstan-type SiteConfig array{
 *   storeName:string, legalName:string, tagline:string, siteUrl:string,
 *   titleSuffix:string, country:string, region:string, city:string,
 *   locale:string, language:string, currency:string, timezone:string,
 *   address:array{street:string, city:string, region:string, postalCode:string, country:string},
 *   geo:array{lat:string, lng:string},
 *   phone:string, whatsapp:string, email:string, openingHours:string,
 *   hasPhysicalLocation:bool,
 *   social:array<string,string>,
 *   regulatory:array<string,string>,
 *   logoPath:string, defaultOgImage:string,
 *   gscVerification:string, ga4MeasurementId:string,
 *   cleanUrls:bool, canonicalRedirect:bool, merchantFeed:bool,
 *   merchantCountry:string, merchantCurrency:string,
 *   defaultMetaDescription:string
 * }
 */
return [
    // --------------------------------------------------------------- identity
    'storeName' => (string) env('SITE_STORE_NAME', env('APP_NAME', '')),
    'legalName' => (string) env('SITE_LEGAL_NAME', ''),
    'tagline' => (string) env('SITE_TAGLINE', ''),
    'siteUrl' => (string) env('SITE_URL', env('APP_URL', '')),
    'titleSuffix' => (string) env('SITE_TITLE_SUFFIX', ''),

    // ----------------------------------------------------------------- locale
    'country' => (string) env('SITE_COUNTRY', ''),
    'region' => (string) env('SITE_REGION', ''),
    'city' => (string) env('SITE_CITY', ''),
    'locale' => (string) env('SITE_LOCALE', ''),
    'language' => (string) env('SITE_LANGUAGE', ''),
    'currency' => (string) env('APP_CURRENCY', env('SITE_CURRENCY', '')),
    'timezone' => (string) env('SITE_TIMEZONE', ''),

    // ---------------------------------------------------------------- contact
    'address' => [
        'street' => (string) env('SITE_ADDRESS_STREET', ''),
        'city' => (string) env('SITE_CITY', ''),
        'region' => (string) env('SITE_REGION', ''),
        'postalCode' => (string) env('SITE_ADDRESS_POSTAL', ''),
        'country' => (string) env('SITE_COUNTRY', ''),
    ],
    'geo' => [
        'lat' => (string) env('SITE_GEO_LAT', ''),
        'lng' => (string) env('SITE_GEO_LNG', ''),
    ],
    'phone' => (string) env('SITE_PHONE', ''),
    'whatsapp' => (string) env('SITE_WHATSAPP', ''),
    'email' => (string) env('SITE_EMAIL', ''),
    'openingHours' => (string) env('SITE_OPENING_HOURS', ''),
    // Only true when the published address is staffed and may be shown.
    'hasPhysicalLocation' => filter_var(env('SITE_HAS_PHYSICAL_LOCATION', 'false'), FILTER_VALIDATE_BOOL),

    // ----------------------------------------------------------------- social
    'social' => array_filter([
        'x' => (string) env('SITE_SOCIAL_X', ''),
        'instagram' => (string) env('SITE_SOCIAL_INSTAGRAM', ''),
        'facebook' => (string) env('SITE_SOCIAL_FACEBOOK', ''),
        'telegram' => (string) env('SITE_SOCIAL_TELEGRAM', ''),
    ], static fn (string $value): bool => $value !== ''),

    // ------------------------------------------------------------- regulatory
    // Published only when filled. Never inferred or invented.
    'regulatory' => array_filter([
        'pharmacyLicenceNumber' => (string) env('REG_STATE_LICENCE', ''),
        'licensingAuthorityName' => (string) env('REG_STATE_BOARD', ''),
        'superintendentPharmacistName' => (string) env('REG_PIC_NAME', ''),
        'superintendentLicenceNumber' => (string) env('REG_PIC_LICENCE', ''),
        'medicinesRegulatorName' => (string) env('REG_MEDICINES_REGULATOR', ''),
        'dataProtectionLawName' => (string) env('REG_DATA_PROTECTION_LAW', ''),
    ], static fn (string $value): bool => $value !== ''),

    // ----------------------------------------------------------------- visual
    'logoPath' => (string) env('SITE_LOGO_PATH', ''),
    'defaultOgImage' => (string) env('SITE_OG_IMAGE', ''),

    // --------------------------------------------------------------- editorial
    // Health content is only marked as medically reviewed when these are set.
    'editorial' => array_filter([
        'authorName' => (string) env('EDITORIAL_AUTHOR_NAME', ''),
        'reviewerName' => (string) env('EDITORIAL_REVIEWER_NAME', ''),
        'reviewerCredentials' => (string) env('EDITORIAL_REVIEWER_CREDENTIALS', ''),
        'reviewerLicence' => (string) env('EDITORIAL_REVIEWER_LICENCE', ''),
        'reviewerNpi' => (string) env('EDITORIAL_REVIEWER_NPI', ''),
        'lastReviewed' => (string) env('EDITORIAL_LAST_REVIEWED', ''),
    ], static fn (string $value): bool => $value !== ''),

    // ---------------------------------------------------------------- tracking
    'gscVerification' => (string) env('GSC_VERIFICATION', ''),
    'ga4MeasurementId' => (string) env('GA4_MEASUREMENT_ID', ''),

    // -------------------------------------------------------------------- SEO
    // true = emit /product/{slug} style URLs; legacy query URLs 301 to them.
    'cleanUrls' => filter_var(env('SEO_CLEAN_URLS', 'false'), FILTER_VALIDATE_BOOL),
    // true = 301 requests whose host/protocol differ from siteUrl.
    'canonicalRedirect' => filter_var(env('SEO_CANONICAL_REDIRECT', 'false'), FILTER_VALIDATE_BOOL),
    // Merchant feed is opt-in; it stays closed until the country is known and
    // the medicines policy for that country has been checked.
    'merchantFeed' => filter_var(env('SEO_MERCHANT_FEED', 'false'), FILTER_VALIDATE_BOOL),
    'merchantCountry' => (string) env('SEO_MERCHANT_COUNTRY', ''),
    'merchantCurrency' => (string) env('APP_CURRENCY', ''),

    'defaultMetaDescription' => (string) env('SITE_DEFAULT_DESCRIPTION', ''),
];
