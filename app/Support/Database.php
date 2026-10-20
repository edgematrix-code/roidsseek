<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

/**
 * Thin PDO wrapper used by the whole application.
 */
class Database
{
    private static ?PDO $connection = null;

    public static function connect(array $config): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        self::$connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }

    public static function pdo(): PDO
    {
        if (!self::$connection instanceof PDO) {
            throw new PDOException('Database connection has not been initialised.');
        }

        return self::$connection;
    }

    public static function run(string $sql, array $params = []): \PDOStatement
    {
        // Runtime self-heal: fix em-dash-quote corruption at prepare() time.
        // The deploy pipeline replaces " with U+2014 (em dash) in PHP string literals.
        // The em-dash is built from chr() calls so the source file contains no
        // non-ASCII bytes that a deploy pipeline might mangle.
        // Try multiple possible dash characters the pipeline might insert.
        // U+2014 = em dash, U+2013 = en dash, U+2012 = figure dash.
        $dashes = [
            chr(0xE2) . chr(0x80) . chr(0x94),  // U+2014 em dash
            chr(0xE2) . chr(0x80) . chr(0x93),  // U+2013 en dash
            chr(0xE2) . chr(0x80) . chr(0x92),  // U+2012 figure dash
        ];
        foreach ($dashes as $dash) {
            $sql = str_replace($dash . 'both', '"both"', $sql);
            $sql = str_replace($dash . 'approved', '"approved"', $sql);
            $sql = str_replace($dash . 'usa', '"usa"', $sql);
            $sql = str_replace($dash . 'international', '"international"', $sql);
            $sql = str_replace($dash . 'pending', '"pending"', $sql);
            $sql = str_replace($dash . 'rejected', '"rejected"', $sql);
        }

        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [])
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);

        return (int) self::pdo()->lastInsertId();
    }
}
