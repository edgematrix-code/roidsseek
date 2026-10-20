<?php

declare(strict_types=1);

namespace App\Support;

class Cart
{
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
        $product = Database::one('SELECT id, stock, is_active FROM products WHERE id = ?', [$productId]);
        if ($product === null || (int) $product['is_active'] !== 1) {
            return false;
        }

        $qty = max(1, $qty);
        $current = self::raw()[$productId] ?? 0;
        $newQty = $current + $qty;
        if ((int) $product['stock'] > 0) {
            $newQty = min($newQty, (int) $product['stock']);
        }

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

        $stock = (int) Database::value('SELECT stock FROM products WHERE id = ?', [$productId]);
        $_SESSION['cart'][$productId] = $stock > 0 ? min($qty, $stock) : $qty;
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
        $products = Database::all("SELECT * FROM products WHERE id IN ($placeholders)", $ids);

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

        $coupon = Database::one('SELECT * FROM coupons WHERE code = ? AND is_active = 1', [$code]);
        if ($coupon === null) {
            self::removeCoupon();
            return null;
        }

        return $coupon;
    }

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

    public static function shipping(): float
    {
        if (self::isEmpty()) {
            return 0.0;
        }

        $afterDiscount = self::subtotal() - self::discount();
        if ($afterDiscount >= (float) config('app.free_shipping_threshold')) {
            return 0.0;
        }

        return (float) config('app.shipping_flat_rate');
    }

    public static function total(): float
    {
        return round(max(0, self::subtotal() - self::discount() + self::shipping()), 2);
    }
}
