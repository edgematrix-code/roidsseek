<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\View;

class AccountController
{
    public function index(): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        View::render('pages/account', [
            'title' => 'My Account - RoidsSeek',
            'user' => $user,
            'orders' => $this->recentOrders((int) $user['id']),
            'errors' => [],
            'section' => 'profile',
        ]);
    }

    public function orders(): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        View::render('pages/account-orders', [
            'title' => 'My Orders - RoidsSeek',
            'user' => $user,
            'orders' => Database::all(
                'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC',
                [$user['id']]
            ),
        ]);
    }

    public function order(string $number): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        $order = Database::one('SELECT * FROM orders WHERE order_number = ? AND user_id = ?', [$number, $user['id']]);
        if ($order === null) {
            http_response_code(404);
            View::render('pages/404', ['title' => 'Order not found - RoidsSeek']);
            return;
        }

        View::render('pages/order', [
            'title' => 'Order ' . $order['order_number'] . ' - RoidsSeek',
            'order' => $order,
            'items' => Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]),
            'justPlaced' => false,
        ]);
    }

    public function updateProfile(): void
    {
        Auth::requireLogin();
        if (!is_post()) {
            redirect('account');
        }
        verify_csrf();

        $name = trim((string) input('name', ''));
        $email = strtolower(trim((string) input('email', '')));
        $phone = trim((string) input('phone', ''));

        $errors = [];
        if (strlen($name) < 2) {
            $errors['name'] = 'Enter your full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        $existing = Database::one('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, Auth::id()]);
        if ($existing !== null) {
            $errors['email'] = 'That email belongs to another account.';
        }

        $user = Auth::user();
        if ($errors !== []) {
            View::render('pages/account', [
                'title' => 'My Account - RoidsSeek',
                'user' => $user,
                'orders' => $this->recentOrders((int) $user['id']),
                'errors' => $errors,
                'section' => 'profile',
            ]);
            return;
        }

        Database::run(
            'UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?',
            [$name, $email, $phone !== '' ? $phone : null, $user['id']]
        );

        flash('success', 'Your profile has been updated.');
        redirect('account');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        if (!is_post()) {
            redirect('account');
        }
        verify_csrf();

        $user = Auth::user();
        $current = (string) input('current_password', '');
        $password = (string) input('password', '');
        $confirm = (string) input('password_confirmation', '');

        $errors = [];
        if (!password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirmation'] = 'The passwords do not match.';
        }

        if ($errors !== []) {
            View::render('pages/account', [
                'title' => 'My Account - RoidsSeek',
                'user' => $user,
                'orders' => $this->recentOrders((int) $user['id']),
                'errors' => $errors,
                'section' => 'password',
            ]);
            return;
        }

        Database::run(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $user['id']]
        );

        flash('success', 'Your password has been changed.');
        redirect('account');
    }

    private function recentOrders(int $userId): array
    {
        return Database::all(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
            [$userId]
        );
    }
}
