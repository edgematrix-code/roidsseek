<?php

declare(strict_types=1);

namespace App\Support;

class Auth
{
    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }

        self::$user = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        if (self::$user === null) {
            unset($_SESSION['user_id']);
        }

        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user ? (int) $user['id'] : null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && $user['role'] === 'admin';
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::one('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::login($user);

        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        self::$user = null;
        session_regenerate_id(true);
    }

    public static function register(string $name, string $email, string $password, ?string $phone = null): array
    {
        $id = Database::insert(
            'INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)',
            [trim($name), strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT), $phone, 'customer']
        );

        $user = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        self::login($user);

        return $user;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            redirect('login', ['next' => $_SERVER['REQUEST_URI'] ?? '']);
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            flash('error', 'You do not have permission to view that page.');
            redirect('');
        }
    }
}
