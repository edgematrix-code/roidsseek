<?php

declare(strict_types=1);

namespace App\Support;

class Nav
{
    private static array $cache = [];

    public static function categories(): array
    {
        return self::$cache['categories'] ??= Database::all(
            'SELECT c.*, (SELECT COUNT(*) FROM product_category pc WHERE pc.category_id = c.id) AS product_count
             FROM categories c ORDER BY c.sort_order, c.name'
        );
    }

    public static function brands(): array
    {
        return self::$cache['brands'] ??= Database::all(
            'SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.is_active = 1) AS product_count
             FROM brands b ORDER BY b.name'
        );
    }

    public static function goals(): array
    {
        return self::$cache['goals'] ??= Database::all('SELECT * FROM goals ORDER BY name');
    }

    public static function count(string $table, string $where = '1'): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM $table WHERE $where");
    }
}
