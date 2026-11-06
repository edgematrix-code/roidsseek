# Site configuration and SEO setup

Every business detail is read from `config/site.php`, which reads the
environment (`.env` or real environment variables). Nothing is hardcoded in
templates, metadata, structured data, the sitemaps, the feed or the footer.

An **empty value means "not supplied"**. Dependent output is omitted instead of
printing `TODO`, and features that need the value stay disabled. Nothing is
inferred from the code, the visitor's IP or a default country.

```bash
cp .env.example .env
php database/migrate.php        # add the SEO/compliance columns once
php tools/config_check.php      # list every field that is still unset
php -S 127.0.0.1:8000 router.php
```

## Health and content rules encoded in the app

- No medical copy is generated. Descriptions, ingredients, strengths, warnings,
  GTINs and registration numbers only come from stored data or the owner.
- Product titles use neutral wording for prescription items
  (`... - Prescription Required`), never "buy without a prescription".
- `Product`/`Drug` schema only uses stored fields. `Drug` fields appear only
  when `drug_schema_reviewed = 1` (set by a pharmacist in the admin product
  editor).
- `Pharmacy` schema and the location/address block appear only when
  `SITE_HAS_PHYSICAL_LOCATION=true` **and** address, phone and opening hours
  are filled.
- Restricted products are `noindex`, excluded from the sitemap, the feed and
  related-product modules.
- Policy pages carry a visible "requires legal review" note. They are templates,
  not legal advice.

## Fields

| Env variable | Config key | Where it appears |
|---|---|---|
| `SITE_STORE_NAME` | `storeName` | Titles, Open Graph, structured data, header, footer |
| `SITE_LEGAL_NAME` | `legalName` | `Organization.legalName`, licence block |
| `SITE_TAGLINE` | `tagline` | Home title/description |
| `SITE_URL` (or `APP_URL`) | `siteUrl` | Canonicals, Open Graph, sitemaps, robots, feed, redirects. **Required in production.** |
| `SITE_TITLE_SUFFIX` | `titleSuffix` | Optional full suffix instead of `\| StoreName` |
| `SITE_COUNTRY` | `country` | Address, Merchant Center, country segment in metadata |
| `SITE_REGION`, `SITE_CITY` | `region`, `city` | Postal address, local schema |
| `SITE_LOCALE` | `locale` | `<html lang>`, `hreflang`, `og:locale`, number formatting |
| `SITE_LANGUAGE` | `language` | Fallback for `<html lang>` |
| `APP_CURRENCY` | `currency` | Prices, `priceCurrency`, feed, Open Graph product price |
| `SITE_TIMEZONE` | `timezone` | Operational timezone (dates stay UTC in the database) |
| `SITE_ADDRESS_STREET`, `SITE_ADDRESS_POSTAL` | `address.street`, `address.postalCode` | `PostalAddress`, licence/contact blocks |
| `SITE_GEO_LAT`, `SITE_GEO_LNG` | `geo` | `Pharmacy.geo` |
| `SITE_PHONE`, `SITE_WHATSAPP` | `phone`, `whatsapp` | Contact page, `Organization`/`Pharmacy` telephone |
| `SITE_EMAIL` | `email` | Support inbox: every contact-form message is forwarded here. Never printed on the site. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | `mail.*` | Authenticated SMTP submission. Leave `MAIL_HOST` blank to fall back to PHP `mail()`, which shared hosts often drop. |
| `MAIL_FROM`, `MAIL_FROM_NAME` | `mail.from`, `mail.from_name` | Sender. Must be on your own domain (default `no-reply@<bare domain>`); `www.*` fails SPF. |
| `SITE_OPENING_HOURS` | `openingHours` | `Pharmacy.openingHours`; pipe-separated, e.g. `Mo-Fr 09:00-18:00\|Sa 10:00-14:00` |
| `SITE_HAS_PHYSICAL_LOCATION` | `hasPhysicalLocation` | Turns the `Pharmacy` schema and address block on/off |
| `SITE_SOCIAL_*` | `social` | `Organization.sameAs` |
| `REG_STATE_LICENCE` | `regulatory.pharmacyLicenceNumber` | Licence block |
| `REG_STATE_BOARD` | `regulatory.licensingAuthorityName` | Licence block |
| `REG_PIC_NAME`, `REG_PIC_LICENCE` | `regulatory.superintendentPharmacistName`, `...LicenceNumber` | Licence block |
| `REG_MEDICINES_REGULATOR` | `regulatory.medicinesRegulatorName` | Licence block and policy wording |
| `REG_DATA_PROTECTION_LAW` | `regulatory.dataProtectionLawName` | Privacy policy wording |
| `SITE_LOGO_PATH` | `logoPath` | Header, `Organization.logo`, Open Graph fallback |
| `SITE_OG_IMAGE` | `defaultOgImage` | Default social preview (1200×630) |
| `SITE_DEFAULT_DESCRIPTION` | `defaultMetaDescription` | Home/fallback meta description |
| `GSC_VERIFICATION` | `gscVerification` | Search Console verification meta tag |
| `GA4_MEASUREMENT_ID` | `ga4MeasurementId` | Reserved for consent-aware analytics |
| `SEO_CLEAN_URLS` | `cleanUrls` | Emit path URLs (`/shop/{slug}`, `/collections/{slug}`) and 301 legacy URLs (on by default) |
| `SEO_CANONICAL_REDIRECT` | `canonicalRedirect` | 301 other hosts/schemes to `siteUrl` |
| `SEO_MERCHANT_FEED` | `merchantFeed` | Enables `/feed/products.xml` |
| `SEO_MERCHANT_COUNTRY` | `merchantCountry` | Feed target country |
| `EDITORIAL_*` | `editorial` | Author/reviewer bylines and `Article.reviewedBy` |

The admin product editor exposes the per-product SEO fields: meta title and
description overrides, image alt text, Rx/OTC status, active ingredient, dosage
form, manufacturer, registration number, NDC, GTIN, restricted, discontinued,
feed-eligible and pharmacist-reviewed.

## Tooling

```bash
npm run config:check              # gaps + disabled features
npm run config:check:production   # fails when siteUrl is missing
npm run seo:check                 # runs against http://127.0.0.1:8000
php tools/seo_check.php --base=https://roidsseek.io
npm run seo:gaps                  # content gap report
npm run seo:og-image              # regenerate assets/images/og-default.png
npm run lint
php database/migrate.php
```

`seo_check.php` exits non-zero for blocking problems (missing canonical, broken
links, restricted/Rx items in the sitemap or feed, JSON-LD parse errors,
price/availability mismatch, `TODO` leakage) so CI can gate on it. Warnings
cover thin copy and missing optional identifiers.

## Indexation rules

| Route | Indexed | How |
|---|---|---|
| Home, `/products`, categories, brands, conditions, products, articles, info pages, `/location` (when enabled) | Yes | `index,follow` + self canonical |
| Pagination (`?page=N`) | Yes | Self canonical, title gets ` - Page N` |
| Filters, sorting (`brand`, `substance`, `location`, `max_price`, `stock`, `sort`, `keyword`) | No | `noindex,follow`, canonical to the clean listing |
| Internal search | No | `noindex,follow` |
| Cart, checkout, order, account, login, register, reset, wishlist-equivalent pages, admin | No | `noindex,nofollow` |
| Restricted products | No | `noindex,follow`; excluded from sitemap/feed/related |
| Discontinued products | n/a | 301 to the closest equivalent/product or category; 410 when nothing exists |
| 404 | n/a | Real 404 + `noindex`; search box and popular categories |

`robots.txt` does **not** block CSS, JS, images or the noindex pages, so Google
can actually see the `noindex` directive. It blocks only `/admin`, mutation
endpoints and faceted query parameters.

While `SEO_NOINDEX=true` the table above is overridden: every page is served
`noindex,nofollow`, and `index.php` adds `X-Robots-Tag: noindex, nofollow` to
every response. For non-HTML files add the header at the server level —
`.htaccess` already does it for Apache, and for nginx add
`add_header X-Robots-Tag "noindex, nofollow" always;` to the `server` block.
The flag is off by default, so the rules in the table apply as written.

## Sitemaps

- `/sitemap.xml` — index
- `/sitemap-products.xml` (chunked above 45,000 URLs), with the image extension
- `/sitemap-categories.xml`, `/sitemap-brands.xml`, `/sitemap-goals.xml`
- `/sitemap-articles.xml`, `/sitemap-pages.xml`, `/sitemap-static.xml`

Only active, indexable, 200-status canonical URLs are included. In
`/sitemap.xml` every `<lastmod>` is the day the sitemap was requested, so the
whole site is re-read; the per-section sitemaps keep the real content dates
(`products.updated_at`, `articles.published_at`).

## Clean URLs and canonical host

Clean URLs are on by default (`SEO_CLEAN_URLS=true`): `/shop/{slug}`,
`/collections/{slug}`, `/brands/{slug}`, `/goals/{slug}`, `/learn/{slug}`,
`/info/{slug}`, `/delivery/{slug}`, `/catalogue`, `/deals`, `/help`, `/news`.
The query form `index.php?p=product&slug=x` and the older `?r=` form both 301
to the path form in one hop, and trailing slashes 301 to the slash-less form.

```dotenv
SEO_CLEAN_URLS=true
SEO_CANONICAL_REDIRECT=true
```

The rewrite lives in `.htaccess` (Apache) and `router.php` (PHP built-in
server). nginx needs it added by hand or every clean URL 404s:

```nginx
location ~ /\.(?!well-known) { deny all; }
location ~ ^/(app|config|database|tools|tests|legacy)/ { deny all; }
location / { try_files $uri $uri/ /index.php?$query_string; }
```

`SEO_CANONICAL_REDIRECT=true` 301s any other host or plain HTTP to `siteUrl`
and should be enabled in production.

## Google Merchant Center

The feed is deliberately closed until the country is known:

- `SEO_MERCHANT_FEED=true` and `SEO_MERCHANT_COUNTRY` are required, otherwise
  `/feed/products.xml` returns 404.
- Only products with `feed_eligible = 1`, `rx_status = 'otc'`, `is_restricted = 0`
  and `discontinued = 0` are included. Prescription-only and restricted items
  are never in the feed.
- Policies used (checked October 2026, re-check before launch):
  <https://support.google.com/merchants/answer/6150151> and
  <https://support.google.com/adspolicy/answer/176031>.

Pharmacy advertising and listing rules vary by country. Eligibility is the
owner's responsibility and may require certification (for example LegitScript
and a national pharmacy accreditation) before Google accepts the feed.

## Search Console

1. Set `GSC_VERIFICATION` to the verification token.
2. Deploy with `SITE_URL` set, then submit `https://roidsseek.io/sitemap.xml`.
3. Enable `SEO_CANONICAL_REDIRECT` and `SEO_CLEAN_URLS` at the same time and
   spot-check old URLs.
