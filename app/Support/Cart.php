<?php

declare(strict_types=1);

namespace App\Support;

class Cart
{
    /** Cheaper delivery service, used unless the customer picks domestic. */
    public const SERVICE_INTERNATIONAL = 'international';
    /** US delivery: 20% more than international. */
    public const SERVICE_DOMESTIC = 'domestic';

    private static ?array $rows = null;

    /** @return array<int,int> product_id => qty */
    public static function raw(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        $clean = [];
        foreach ($cart as $id => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $clean[(int) $id] = $qty;
            }
        }

        return $clean;
    }

    public static function add(int $productId, int $qty = 1): bool
    {
        $product = Database::one('SELECT id, price, is_active FROM products WHERE id = ?', [$productId]);
        if ($product === null || (int) $product['is_active'] !== 1) {
            return false;
        }

        // Unpriced or below-minimum products cannot be ordered. Stock is not a
        // gate: every priced product is always available to buy.
        if ((float) $product['price'] < min_product_price()) {
            return false;
        }

        $qty = max(1, $qty);
        $current = self::raw()[$productId] ?? 0;
        $newQty = $current + $qty;

        $_SESSION['cart'][$productId] = $newQty;
        self::$rows = null;

        return true;
    }

    public static function update(int $productId, int $qty): void
    {
        if ($qty <= 0) {
            self::remove($productId);
            return;
        }

        if (!isset($_SESSION['cart'][$productId])) {
            return;
        }

        $_SESSION['cart'][$productId] = $qty;
        self::$rows = null;
    }

    public static function remove(int $productId): void
    {
        unset($_SESSION['cart'][$productId]);
        self::$rows = null;
    }

    public static function clear(): void
    {
        unset($_SESSION['cart'], $_SESSION['coupon']);
        self::$rows = null;
    }

    public static function count(): int
    {
        return array_sum(self::raw());
    }

    public static function isEmpty(): bool
    {
        return self::raw() === [];
    }

    /** Basket rows with product data joined in. */
    public static function items(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }

        $raw = self::raw();
        if ($raw === []) {
            return self::$rows = [];
        }

        $ids = array_keys($raw);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        // The brand is joined in because products.manufacturer is optional free
        // text: most rows only carry a brand, and the cart modal shows whichever
        // of the two is available as the manufacturer.
        $products = Database::all(
            "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug
             FROM products p LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.id IN ($placeholders)",
            $ids
        );

        $rows = [];
        foreach ($products as $product) {
            $qty = $raw[(int) $product['id']] ?? 0;
            if ($qty <= 0) {
                continue;
            }
            $price = (float) $product['price'];
            $rows[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_price' => $price,
                'line_total' => round($price * $qty, 2),
            ];
        }

        return self::$rows = $rows;
    }

    public static function subtotal(): float
    {
        $total = 0.0;
        foreach (self::items() as $row) {
            $total += $row['line_total'];
        }

        return round($total, 2);
    }

    public static function applyCoupon(string $code): array
    {
        $coupon = Database::one(
            'SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) AND is_active = 1',
            [trim($code)]
        );

        if ($coupon === null) {
            return [false, 'That coupon code is not valid.'];
        }

        if ($coupon['expires_at'] !== null && $coupon['expires_at'] < date('Y-m-d')) {
            return [false, 'That coupon has expired.'];
        }

        if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            return [false, 'That coupon has reached its usage limit.'];
        }

        if (self::subtotal() < (float) $coupon['min_subtotal']) {
            return [false, sprintf('This coupon requires a minimum order of %s.', money($coupon['min_subtotal']))];
        }

        $_SESSION['coupon'] = $coupon['code'];

        return [true, 'Coupon "' . $coupon['code'] . '" applied.'];
    }

    public static function removeCoupon(): void
    {
        unset($_SESSION['coupon']);
    }

    public static function coupon(): ?array
    {
        $code = $_SESSION['coupon'] ?? null;
        if (!$code) {
            return null;
        }

        $coupon = Database::one('SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) AND is_active = 1', [$code]);

        // Re-validate on every read: a coupon that expired, hit its usage limit
        // or no longer meets the basket minimum must stop discounting at once
        // (including when items are removed after the code was applied).
        $expired = $coupon !== null
            && $coupon['expires_at'] !== null
            && $coupon['expires_at'] < date('Y-m-d');
        $exhausted = $coupon !== null
            && $coupon['usage_limit'] !== null
            && (int) $coupon['used_count'] >= (int) $coupon['usage_limit'];
        $belowMinimum = $coupon !== null
            && self::subtotal() < (float) $coupon['min_subtotal'];

        if ($coupon === null || $expired || $exhausted || $belowMinimum) {
            self::removeCoupon();
            return null;
        }

        return $coupon;
    }

    /** Minimum merchandise value (subtotal) required to place an order. */
    public static function minimumOrder(): float
    {
        return (float) config('app.min_order_total');
    }

    /** True once the basket subtotal reaches the minimum order value. */
    public static function meetsMinimumOrder(): bool
    {
        return self::subtotal() >= self::minimumOrder();
    }

    /** How much more the customer has to add to reach the minimum order. */
    public static function minimumOrderShortfall(): float
    {
        return round(max(0, self::minimumOrder() - self::subtotal()), 2);
    }

    /**
     * Promo code that unlocks the bulk discount and free delivery.
     *
     * The offer itself lives in the `coupons` table (fixed amount, minimum
     * subtotal), so an administrator can change it without a deploy; this code
     * only decides whether the free-delivery perk is active.
     */
    public static function promoCode(): string
    {
        return strtoupper(trim((string) config('app.promo_code')));
    }

    /** True when the applied coupon is the configured promo code. */
    public static function promoApplied(): bool
    {
        $coupon = self::coupon();

        return $coupon !== null
            && self::promoCode() !== ''
            && strtoupper((string) $coupon['code']) === self::promoCode();
    }

    /** Discount from the applied coupon, capped at the subtotal. */
    public static function discount(): float
    {
        $coupon = self::coupon();
        if ($coupon === null) {
            return 0.0;
        }

        $subtotal = self::subtotal();
        $discount = $coupon['type'] === 'percent'
            ? $subtotal * ((float) $coupon['value'] / 100)
            : (float) $coupon['value'];

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Delivery cost for the basket.
     *
     * International is the cheaper service; US domestic costs 20% more. Until
     * the customer reaches checkout the service they picked in the cart modal
     * decides; once a shipping country is known that country wins, so a US
     * address is always charged the domestic rate.
     */
    public static function shipping(string $country = ''): float
    {
        if (self::isEmpty()) {
            return 0.0;
        }

        if (self::qualifiesForFreeShipping()) {
            return 0.0;
        }

        $domestic = $country !== ''
            ? self::isDomestic($country)
            : self::shippingService() === self::SERVICE_DOMESTIC;

        return $domestic
            ? (float) config('app.shipping_domestic_rate')
            : (float) config('app.shipping_international_rate');
    }

    /** Delivery service chosen in the cart modal; international by default. */
    public static function shippingService(): string
    {
        return ($_SESSION['shipping_service'] ?? '') === self::SERVICE_DOMESTIC
            ? self::SERVICE_DOMESTIC
            : self::SERVICE_INTERNATIONAL;
    }

    public static function setShippingService(string $service): void
    {
        $_SESSION['shipping_service'] = trim($service) === self::SERVICE_DOMESTIC
            ? self::SERVICE_DOMESTIC
            : self::SERVICE_INTERNATIONAL;
    }

    /** @return array{international:float,domestic:float,threshold:float} */
    public static function shippingRates(): array
    {
        return [
            'international' => (float) config('app.shipping_international_rate'),
            'domestic' => (float) config('app.shipping_domestic_rate'),
            'threshold' => (float) config('app.free_shipping_threshold'),
        ];
    }

    /** True once the basket qualifies for free delivery. */
    public static function hasFreeShipping(): bool
    {
        return self::qualifiesForFreeShipping();
    }

    /**
     * Free delivery is part of the promo offer: it is granted only while the
     * promo code is applied (which itself requires the offer's minimum basket).
     */
    public static function qualifiesForFreeShipping(): bool
    {
        return !self::isEmpty() && self::promoApplied();
    }

    /** True when a free-text country means the United States. */
    public static function isDomestic(string $country): bool
    {
        $value = strtolower(trim(preg_replace('/\s+/', ' ', $country) ?? ''));
        $value = rtrim($value, '.');

        return in_array($value, ['us', 'u.s', 'usa', 'u.s.a', 'united states', 'united states of america'], true);
    }

    public static function total(string $country = ''): float
    {
        return round(max(0, self::subtotal() - self::discount() + self::shipping($country)), 2);
    }
}
