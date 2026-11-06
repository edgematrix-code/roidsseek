<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Catalogue price rules.
 *
 * Nothing sells below the configured minimum, but the band beneath that minimum
 * keeps its internal spread instead of collapsing onto one repeated figure: the
 * cheapest imported item lands exactly on the floor and the rest fan out above
 * it in their original order. Products the source catalogue never priced are
 * priced from their closest peers (same substance, then same brand) so the shop
 * is varied and sellable rather than a wall of identical prices.
 *
 * Everything here is deterministic, so a seed and a re-run of the migration
 * always produce the same figures for the same catalogue.
 */
final class Pricing
{
    /** Minimum sellable price (config app.min_product_price). */
    public static function floor(): float
    {
        return max(0.0, (float) config('app.min_product_price'));
    }

    /**
     * Offset that moves the cheapest source price onto the floor, so a whole
     * sub-floor price band can be lifted without being flattened.
     *
     * @param  array<int, float|int|string> $sourcePrices
     */
    public static function liftOffset(array $sourcePrices): float
    {
        $floor = self::floor();
        $positive = array_filter(
            array_map('floatval', $sourcePrices),
            static fn (float $price): bool => $price > 0
        );

        if ($floor <= 0 || $positive === []) {
            return 0.0;
        }

        return max(0.0, $floor - min($positive));
    }

    /** Lift a below-floor source price into the band above the floor. */
    public static function lift(float $sourcePrice, float $offset): float
    {
        if ($sourcePrice <= 0) {
            return 0.0;
        }

        return max(self::floor(), round($sourcePrice + $offset));
    }

    /**
     * Deterministic price for a product the source catalogue never priced:
     * the peer median moved by -8% to +16%, stable for the given key so repeated
     * runs agree and the catalogue keeps a spread of figures.
     */
    public static function derive(string $key, float $base): float
    {
        $floor = self::floor();
        $base = $base > 0 ? $base : $floor;
        $factor = 0.92 + ((crc32($key) % 2401) / 10000);

        return max($floor, round($base * $factor));
    }

    /**
     * Median of the positive values, used to find a peer price.
     *
     * @param  array<int, float|int|string> $values
     */
    public static function median(array $values): float
    {
        $values = array_values(array_filter(
            array_map('floatval', $values),
            static fn (float $value): bool => $value > 0
        ));
        if ($values === []) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
