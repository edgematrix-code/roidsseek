# SEO implementation report

Date: 2026-10-21 · Site URL: `https://roidsseek.io`

## 1. Audit

| Item | Finding |
|---|---|
| Framework | Dependency-free PHP 8.2+ (tested on 8.5), single front controller `index.php?r=route` |
| Router | `index.php` switch; `router.php` for the built-in server; `.htaccess` for Apache |
| TypeScript | None — PHP app with no build step |
| Hosting target | Apache/shared hosting or the PHP built-in server (Herd locally) |
| Data layer | MySQL 8 via PDO (`app/Support/Database.php`), 423 active products |
| Payment | No provider integration. Now Bitcoin-only, confirmed on the order page |
| Auth | PHP sessions, CSRF tokens, `password_hash()` |
| Rendering | 100% server-rendered; prices, descriptions and listings are in the initial HTML |

Route inventory (all server-rendered): home, `/products` (`all`), search, sale,
category, brand, condition/goal, shipping listing, product, article list and
detail, events, FAQ, info pages, contact, cart, checkout, order confirmation,
login/register/forgot/reset, account and orders, admin CRUD, newsletter.
There is no wishlist, order-tracking or prescription-upload route in this
codebase.

Data model gaps found: no `restricted`, Rx/OTC, GTIN, alt text, meta overrides,
discontinued, feed eligibility or medical-review fields. Added by
`database/migrate.php` and mirrored in `database/schema.sql`.

Performance baseline: not measured in this SEO-only pass (no Lighthouse or
bundle report requested). The app serves no framework bundle; CSS/JS are local
static files.

## 2. What was implemented, by phase

### Phase 0 — configuration
- `config/site.php` — one typed config source for identity, locale, contact,
  address, social, regulatory, editorial, SEO flags.
- `app/Support/Site.php` — placeholder detection, absolute URL building,
  clean/legacy route mapping, gap detection.
- `tools/config_check.php` (`npm run config:check`) lists every unset field and
  the features it disables; `--production` fails when `siteUrl` is missing.
  `app/bootstrap.php` also refuses to serve when `APP_ENV=production` and the
  URL is missing.
- `SETUP.md` documents every field and where it appears.

### Phase 1 — audit/data model
- `database/migrate.php` — idempotent migration for the SEO/compliance columns
  and indexes; also normalises the existing `rx_status` enum.
- `database/schema.sql` updated for fresh installs.
- Admin product editor and content editor expose the new fields.
- `tools/seo_check.php --gaps` produces the gap report.

### Phase 2 — host, URLs, indexation
- Absolute URLs are always built from `siteUrl` (`app/Support/Site.php`).
- `SEO_CANONICAL_REDIRECT` (301 host/scheme) and `SEO_CLEAN_URLS` (readable
  paths, one-hop 301 from legacy `?r=` URLs, trailing-slash 301).
- `index.php`, `router.php`, `.htaccess` route `/robots.txt`, `/sitemap*.xml`
  and `/feed/products.xml`.
- `app/Controllers/SeoController.php`: robots, sitemap index, per-section
  sitemaps with image extension and real `lastmod`.
- `noindex` rules: cart, checkout, order, auth, account, admin, search, facets,
  restricted products. 404 returns a real 404 with search and categories.
- Discontinued products 301 to a same-substance/same-brand replacement, then
  category, then 410.

### Phase 3 — metadata
- `app/Support/Seo.php` + `app/Views/partials/seo-head.php`: unique titles,
  descriptions, self canonicals, robots, Open Graph (including product price
  and availability), Twitter cards, `og:locale`, `hreflang`, Search Console
  placeholder, `<html lang>` from config.
- `tools/make_og_image.php` generates `assets/images/og-default.png` (1200×630).
- Legacy keyword-stuffing meta tags removed.

### Phase 4 — structured data
- `app/Support/JsonLd.php`: `Organization`, `WebSite` + `SearchAction`,
  conditional `Pharmacy`, `Product` + `Offer`, optional pharmacist-reviewed
  `Drug` properties, `CollectionPage` + `ItemList`, `Article` with
  `reviewedBy`, `BreadcrumbList`.
- JSON-LD is escaped and parsed by `seo_check.php`.

### Phase 5 — trust signals
- Footer links every existing informational page.
- Config-driven contact details and licence/regulatory block
  (`app/Views/partials/contact-details.php`, `regulatory.php`).
- Medical disclaimer on product and article pages; Rx notice on Rx products.
- Policy pages show a visible "requires professional legal review" note.
- Author/reviewer bylines use configured or per-article reviewer data only.

### Phase 6 — Merchant Center
- `/feed/products.xml` with the Google RSS namespaces, built from the same
  price/availability fields as the page and schema.
- Disabled (404) until `SEO_MERCHANT_FEED=true` and a country are set. Only
  `feed_eligible=1`, OTC, unrestricted products are included.
- Policy links documented in `SETUP.md`; eligibility still depends on the
  country and Google certification.

### Phase 7 — on-page
- Product specs table, manufacturer/registration/Rx rows, LCP image
  attributes, out-of-stock notify link and category links, related products,
  gallery alt fallback, disclaimer.
- Category/listing: intro, factual SEO block below the grid, breadcrumbs,
  crawlable pagination.
- Internal links: footer pages, categories in nav, related products, guides.
- No auto-generated city/drug doorway pages.

### Payment change requested during the work
- `config/config.php` reads `BTC_ADDRESS`, `BTC_LABEL`, `BTC_CONFIRMATIONS`,
  `BTC_USD_RATE`.
- Checkout offers Bitcoin only; orders are stored as `bitcoin`.
- Order confirmation shows a styled Bitcoin panel: amount (when a rate is
  configured), copyable address, BIP21 wallet link, steps, confirmation count
  and a "send only BTC" warning. Address:
  `bc1q9take6d97hd9wthp7g2g3kttrly0k4sx84w2c8`.

## 3. Gap report (generated)

```text
Active products: 423
  missing full description        0   (but all 423 are thin: < 80 words)
  description under 80 words      423
  missing or unreadable image     0
  missing image alt text          423 (template falls back to the product name)
  Rx/OTC status not classified    423
  missing registration number     423
  duplicate generated titles        5 (need manual meta titles)
Categories without copy:          0
```

All 423 product descriptions are short, generic seed copy with the same
structure. Five product pairs also share name, strength and brand, so their
generated titles collide until a manual meta title (or a distinct name) is set. They need owner/pharmacist-written or manufacturer-supplied copy;
no text was invented. Run `npm run seo:gaps` at any time for the current list.

## 4. Compliance summary

Excluded from indexing: cart, checkout, order pages, login/register/reset,
account, admin, internal search, mutation endpoints (`newsletter`, `review`),
faceted/sorted listings, restricted products. Excluded from the sitemap:
restricted, discontinued and inactive products, unpublished articles, and any
URL that is not a 200 canonical. Excluded from the feed: everything except
`feed_eligible`, OTC, unrestricted, non-discontinued products — currently zero
products, so the feed is intentionally empty.

`robots.txt` allows CSS/JS/images and the noindex pages, so the meta directive
is visible to crawlers; it only blocks admin, mutations and facet parameters.

No review/rating markup is emitted: the catalogue's reviews are demo data and
fake review markup is prohibited. Add `AggregateRating` only after real,
visible customer reviews exist.

Policy references: Google Merchant Center healthcare & medicines
<https://support.google.com/merchants/answer/6150151> and Google Ads healthcare
and medicines <https://support.google.com/adspolicy/answer/176031>.

## 5. Decisions for review

1. **Canonical host** is `https://roidsseek.io` from `SITE_URL`. Turn on
   `SEO_CANONICAL_REDIRECT=true` in production.
2. **Clean URLs are implemented but off** (`SEO_CLEAN_URLS=false`) because the
   existing regression suite and the previous `.env.example` assume legacy
   `?r=` URLs. Enabling it gives `/product/{slug}` plus one-hop 301s from old
   URLs; the test suite's URL assertions would then need updating.
3. **Noindex vs Disallow:** private pages stay crawlable with `noindex`;
   `robots.txt` only blocks admin/mutations/facets. This is deliberate.
4. **Facet whitelist is empty.** No filtered combination is indexable. Add
   terms only when there is real search demand.
5. **Feed scope is empty by default.** Confirm the country, medicine policy and
   certification, then mark individual OTC products feed-eligible.
6. **Rx products stay indexable** with neutral "Prescription Required" titles
   and a visible prescription notice; restricted products are noindex.
7. **Pharmacy schema is disabled** until `SITE_HAS_PHYSICAL_LOCATION=true` plus
   address, phone and hours.
8. **Discontinued replacement** is a heuristic (same substance, then brand,
   then category). Review the first result per product.
9. **Copy and reviews are not generated.** Product descriptions and guide
   content remain owner/pharmacist work.
10. **Language/locale are unset**, so `<html lang>` and `hreflang` are omitted
    until `SITE_LOCALE`/`SITE_LANGUAGE` are configured.

## 6. Manual checklist

- [ ] Fill in `.env` (read by `config/site.php`) — start with
      `php tools/config_check.php`.
- [ ] Set `SITE_LOCALE`, `SITE_LANGUAGE`, `SITE_COUNTRY`, `SITE_TIMEZONE`.
- [ ] Verify `roidsseek.io` in Search Console (`GSC_VERIFICATION`), submit
      `https://roidsseek.io/sitemap.xml`.
- [ ] Enable `SEO_CLEAN_URLS` and `SEO_CANONICAL_REDIRECT`, then spot-check old
      links and update the test assertions that expect `?r=`.
- [ ] Create a Google Business Profile only if a staffed physical location
      exists; set `SITE_HAS_PHYSICAL_LOCATION=true` and fill address/phone/hours.
- [ ] Create Merchant Center, confirm medicine-policy eligibility for the
      country, then enable `SEO_MERCHANT_FEED` and mark compliant OTC products.
- [ ] Get pharmacist review of all health copy; set reviewer fields; mark
      products `drug_schema_reviewed` only after review.
- [ ] Professional legal review of every policy page.
- [ ] Run keyword research for the target market.
- [ ] Collect real customer reviews before enabling review markup.
- [ ] Write guide content (outlines only were possible; none were invented).
- [ ] Measure Lighthouse mobile and bundle sizes after content is final.

## 7. File changelog

**Created:** `config/site.php`, `app/Support/Site.php`, `app/Support/Seo.php`,
`app/Support/JsonLd.php`, `app/Views/partials/seo-head.php`,
`app/Views/partials/regulatory.php`, `app/Views/partials/contact-details.php`,
`app/Controllers/SeoController.php`, `database/migrate.php`,
`tools/config_check.php`, `tools/seo_check.php`, `tools/lint.php`,
`tools/make_og_image.php`, `package.json`, `SETUP.md`, `SEO-REPORT.md`,
`assets/images/og-default.png`.

**Modified:** `app/helpers.php`, `app/bootstrap.php`, `app/Support/Nav.php`,
`app/Controllers/StoreController.php`, `app/Controllers/CheckoutController.php`,
`app/Controllers/AdminController.php`, `app/Controllers/AdminContentController.php`,
`app/Views/layout.php`, `app/Views/pages/{listing,product,article,page,contact,404,checkout,order}.php`,
`app/Views/partials/{footer,product-card}.php`,
`app/Views/admin/product-form.php`, `config/config.php`, `database/schema.sql`,
`index.php`, `router.php`, `.htaccess`, `.env.example`, `assets/css/site.css`.

**Deleted:** none. No new runtime dependencies were added.
