# RoidsSeek — PHP storefront & admin

A dependency-free PHP 8 + MySQL storefront built from the original static
pages. Every page is server-rendered, the catalogue lives in MySQL, and the
admin console provides full create/read/update/delete control over products,
categories, brands, goals, articles, events, FAQs, pages, coupons, reviews,
messages, orders and customer accounts.

## Requirements

- PHP 8.2+ with `pdo_mysql` and `mbstring`
- MySQL 8.0+
- No Composer packages — the app ships its own micro framework

## Setup

1. **Create the database**

   ```sql
   CREATE DATABASE roidsseek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Configure the connection**

   Credentials live in a `.env` file in the project root, never in code:

   ```bash
   cp .env.example .env
   ```

   ```dotenv
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=roidsseek
   DB_USERNAME=root
   DB_PASSWORD=secret

   ADMIN_EMAIL=admin@roidsseek.com
   ADMIN_PASSWORD=choose-a-strong-password
   ```

   `app/bootstrap.php` loads `.env` before the configuration is read, and
   `config/config.php` only reads `env()` values, so no password is ever
   committed. `.env` is git-ignored; `.env.example` documents every supported
   key (`APP_NAME`, `APP_URL`, `APP_DEBUG`, `APP_CURRENCY`,
   `FREE_SHIPPING_THRESHOLD`, `SHIPPING_FLAT_RATE`, `DB_CHARSET` included).
   Real environment variables take precedence over the file, so a deployment
   can inject `DB_PASSWORD` without touching anything on disk.

   `ADMIN_EMAIL` and `ADMIN_PASSWORD` are used only by the seeder (step 3).
   The seeder refuses to run when `ADMIN_PASSWORD` is empty.

3. **Build the schema and import the catalogue**

   ```bash
   php database/seed.php --fresh
   ```

   `--fresh` drops and recreates the tables. Without it the script refuses to
   run when the `products` table already contains rows.

   The import creates 11 categories, 6 goals, 20 brands, 423 products,
   12 articles, 7 events, 15 FAQs, 3 legal/info pages, demo reviews and
   3 demo orders. The data comes from `database/data/*.json`, which was
   extracted from the static pages.

4. **Run it**

   ```bash
   php -S 127.0.0.1:8000 router.php
   ```

   Open <http://127.0.0.1:8000/index.php>. `router.php` is a small front
   controller for the built-in server that returns 404 for `.env`, other
   dotfiles and the `app/`, `config/`, `database/`, `tests/` directories, so
   the credentials file can never be downloaded. Pass `-t .` and a plain
   `index.php` only if the extra protection is not needed.

   For Apache, point the document root at this directory — `.htaccess`
   already blocks dotfiles and source directories, and `index.php` handles
   every route through `?r=<route>`. For nginx add:

   ```nginx
   location ~ /\.(?!well-known) { deny all; }
   location ~ ^/(app|config|database|tests|legacy)/ { deny all; }
   ```

## Demo accounts

| Role     | Email                    | Password      |
|----------|--------------------------|---------------|
| Admin    | `admin@roidsseek.com`    | `Admin@12345` |
| Customer | `customer@example.com`   | `Customer@123`|

The admin password comes from `ADMIN_PASSWORD` in `.env` at seed time —
replace `Admin@12345` there with a strong password before seeding, and change
the customer password before exposing the site publicly.

## Routes

Storefront: ``, `all`, `search`, `sale`, `category`, `brand`, `goal`,
`shipping`, `product`, `review`, `cart`, `cart/add`, `cart/update`,
`cart/remove`, `cart/clear`, `cart/coupon`, `checkout`, `order`, `login`,
`register`, `logout`, `forgot`, `reset`, `account`, `account/orders`,
`account/order`, `account/profile`, `account/password`, `faq`, `articles`,
`article`, `events`, `page`, `contact`, `newsletter`.

Admin (requires `role = admin`): `admin`, `admin/products`, `admin/product`,
`admin/product/save`, `admin/product/delete`, `admin/orders`, `admin/order`,
`admin/order/status`, `admin/coupons`, `admin/coupon/save`,
`admin/coupon/delete`, `admin/messages`, `admin/message/delete`,
`admin/reviews`, `admin/review/status`, `admin/review/delete`, `admin/users`,
`admin/user/role`, `admin/user/delete`, plus the generic content console:
`admin/content`, `admin/content/edit`, `admin/content/save`,
`admin/content/delete`.

The content console is driven by a single annotated map in
`app/Controllers/AdminContentController.php`; `entity` selects the table
(`categories`, `brands`, `goals`, `articles`, `events`, `faqs`, `pages`).
Slugs are generated from the name/title when left blank and duplicates are
rejected. `/admin.php` is a direct entry point that boots the same router and
shows the dashboard.

## Behaviour notes

- Sessions are cookie based (`roidsseek_session`), CSRF tokens protect every
  mutating form, and passwords use `password_hash()`/`password_verify()`.
- Guest checkout is supported. Orders are written inside a transaction and
  stock is decremented; coupon usage counts are incremented on checkout.
- Cart contents live in the session, priced from the database on every
  request. Shipping is free above $399, otherwise a flat $25.
- Reviews are submitted as `pending` and only appear once approved in
  `admin/reviews`.
- There is no mail transport configured. `forgot` generates a reset link and
  displays it on the page instead of emailing it.
- Product images are served from `assets/products/`; the helper
  `image_url()` falls back to `assets/images/placeholder.svg`.

## Tests

The verification suites in `tests/` drive the app over HTTP, so start the dev
server first and install `requests` (`pip install requests`):

```bash
php -S 127.0.0.1:8000 router.php &
python tests/e2e.py        # storefront, auth, cart, checkout, admin CRUD
python tests/admin_crud.py # /admin.php entry, content CRUD, customer management
python tests/security.py   # admin gating, CSRF, password reset, order isolation
python tests/flows.py      # reviews, filters, pagination, account orders
python tests/logged_in_checkout.py  # order ownership, stock, coupon counter
```

Each suite prints `PASS`/`FAIL` per check and exits non-zero on failure. They
read the admin login and MySQL credentials from `.env` (via `tests/envfile.py`;
real environment variables win), so no password is duplicated in the tests.
They create temporary users, orders and coupons; remove those rows afterwards
if you want a pristine demo dataset (or re-run `php database/seed.php --fresh`).

## Structure

```
index.php               front controller / router
router.php              built-in-server router that blocks .env and sources
.env.example            documented environment template (copy to .env)
app/bootstrap.php       config, session, helpers, autoload
app/Support/            Database, Auth, Cart, View, Nav
app/Controllers/        Store, Cart, Checkout, Auth, Account, Admin
app/Views/              layout, pages, partials, admin
config/config.php       configuration (reads env() only, no secrets)
database/schema.sql     DDL
database/seed.php       schema + catalogue importer
database/import_static.py  rebuilds data/*.json from the legacy pages
assets/                 css, js, product images, branding
legacy/                 original static pages (reference only)
```
