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
