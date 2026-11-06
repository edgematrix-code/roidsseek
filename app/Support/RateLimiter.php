<?php

declare(strict_types=1);

namespace App\Support;

/** Lightweight, per-key rate limiter backed by the `rate_limiter` table. */
final class RateLimiter
{
    private const WINDOW_SECONDS = 60;
    private const LIMIT = 20;

    /**
     * Record an attempt and return true when the key is now throttled.
     */
    public static function consume(string $key): bool
    {
        $pdo = Database::pdo();
        $expires = gmdate('Y-m-d H:i:s', time() + self::WINDOW_SECONDS);

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO rate_limiter (`key`, value, expires_at) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE value = value + 1, expires_at = ?'
            );
            $stmt->execute([$key, $expires, $expires]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return self::count($key) >= self::LIMIT;
    }

    /** Current attempts for an active key (within its window). */
    public static function count(string $key): int
    {
        $count = Database::value(
            'SELECT value FROM rate_limiter WHERE `key` = ? AND expires_at > UTC_TIMESTAMP() ORDER BY id DESC LIMIT 1',
            [$key]
        );

        return $count === null ? 0 : (int) $count;
    }

    public static function clear(string $key): void
    {
        Database::run('DELETE FROM rate_limiter WHERE `key` = ?', [$key]);
    }
}
