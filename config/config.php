<?php

/**
 * Application configuration.
 *
 * Nothing secret lives in this file: every value is read from the environment,
 * which app/bootstrap.php fills from the project .env file (copy
 * .env.example to .env and fill it in). Real environment variables take
 * precedence over the file, so production can override it without edits.
 */
$config = [
    'app' => [
        'name' => env('APP_NAME', 'RoidsSeek'),
        'url' => (string) env('APP_URL', ''),
        'currency' => env('APP_CURRENCY', 'USD'),
        // The promo offer: a basket at or above the minimum subtotal gets
        // bulk_discount_amount off, and the same code additionally unlocks free
        // delivery. Both only apply while the promo code is entered. These
        // values seed the promo coupon in the `coupons` table.
        'promo_code' => (string) env('PROMO_CODE', 'ROIDSSEEK2026'),
        'free_shipping_threshold' => (float) env('FREE_SHIPPING_THRESHOLD', 1000.00),
        'bulk_discount_threshold' => (float) env('BULK_DISCOUNT_THRESHOLD', 1000.00),
        'bulk_discount_amount' => (float) env('BULK_DISCOUNT_AMOUNT', 200.00),
        // No product is sold below this price. Enforced in the admin editor,
        // the cart and checkout, and applied to existing rows by
        // database/migrate.php.
        'min_product_price' => (float) env('MIN_PRODUCT_PRICE', 100.00),
        // Minimum merchandise value (basket subtotal, before discounts and
        // delivery) needed to place an order.
        'min_order_total' => (float) env('MIN_ORDER_TOTAL', 500.00),
        // International delivery is the cheaper service; US domestic costs 20%
        // more (25.00 -> 30.00). SHIPPING_FLAT_RATE is the older single-rate
        // key and is still read as the international fallback.
        'shipping_international_rate' => (float) env(
            'SHIPPING_INTERNATIONAL_RATE',
            env('SHIPPING_FLAT_RATE', 25.00)
        ),
        'shipping_domestic_rate' => (float) env('SHIPPING_DOMESTIC_RATE', 30.00),
        'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    ],
    // Crypto payment methods: Bitcoin, Ethereum, USDT and USDC. Both addresses are public
    // receive addresses; a private key or seed phrase must never be stored here.
    'payments' => [
        'btc' => [
            'address' => trim((string) env('BTC_ADDRESS', '')),
            'label' => (string) env('BTC_LABEL', ''),
            'confirmations' => max(1, (int) env('BTC_CONFIRMATIONS', 2)),
            'usd_rate' => (float) env('BTC_USD_RATE', 0),
        ],
        'eth' => [
            // Default receive address so a fresh install can accept ETH without
            // editing .env; ETH_ADDRESS still overrides it.
            'address' => trim((string) env('ETH_ADDRESS', '0xaB270D8d31C2fBE1fE0B5D2E9A974c44AA821c10')),
            'label' => (string) env('ETH_LABEL', ''),
            'confirmations' => max(1, (int) env('ETH_CONFIRMATIONS', 12)),
            'usd_rate' => (float) env('ETH_USD_RATE', 0),
        ],
        'usdt' => [
            'address' => trim((string) env('USDT_ADDRESS', 'TH7yVRLDtWoBkemNupVKusgNCEzHccnqP7')),
            'label' => (string) env('USDT_LABEL', ''),
            'confirmations' => max(1, (int) env('USDT_CONFIRMATIONS', 12)),
            'usd_rate' => (float) env('USDT_USD_RATE', 1.0),
        ],
        'usdc' => [
            'address' => trim((string) env('USDC_ADDRESS', '0xaB270D8d31C2fBE1fE0B5D2E9A974c44AA821c10')),
            'label' => (string) env('USDC_LABEL', ''),
            'confirmations' => max(1, (int) env('USDC_CONFIRMATIONS', 12)),
            'usd_rate' => (float) env('USDC_USD_RATE', 1.0),
        ],
        'token' => (string) env('PAYMENT_TOKEN', 'Bitcoin (BTC)'),
        'network' => (string) env('PAYMENT_NETWORK', 'Bitcoin'),
        'guest_secret' => (string) env('PAYMENT_GUEST_SECRET', ''),
        'guest_ttl_minutes' => (int) env('PAYMENT_GUEST_TTL_MINUTES', 1440),
        'window_minutes' => (int) env('PAYMENT_WINDOW_MINUTES', 15),
    ],
    // Outgoing mail. With MAIL_HOST empty the app uses PHP mail(); setting
    // MAIL_HOST submits through authenticated SMTP instead, which is what most
    // shared hosts need for the message to actually be delivered.
    'mail' => [
        'host' => (string) env('MAIL_HOST', ''),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => (string) env('MAIL_USERNAME', ''),
        'password' => (string) env('MAIL_PASSWORD', ''),
        // tls = STARTTLS on 587, ssl = implicit TLS on 465, none = plain.
        'encryption' => (string) env('MAIL_ENCRYPTION', 'tls'),
        // Defaults to no-reply@<bare domain>. Never www.*: no SPF record covers it.
        'from' => (string) env('MAIL_FROM', ''),
        'from_name' => (string) env('MAIL_FROM_NAME', ''),
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (string) env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'roidsseek'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => (string) env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
];

// Optional legacy override; .env is the supported way to configure the app.
if (is_file(__DIR__ . '/local.php')) {
    $local = require __DIR__ . '/local.php';
    foreach ($local as $section => $values) {
        $config[$section] = array_merge($config[$section] ?? [], $values);
    }
}

return $config;
