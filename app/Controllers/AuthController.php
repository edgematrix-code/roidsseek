<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\View;

class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            redirect('account');
        }

        $errors = [];
        if (is_post()) {
            verify_csrf();
            $email = (string) input('email', '');
            $password = (string) input('password', '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Enter a valid email address.';
            }
            if ($password === '') {
                $errors['password'] = 'Enter your password.';
            }

            if ($errors === []) {
                if (Auth::attempt($email, $password)) {
                    if (Auth::isAdmin()) {
                        flash('success', 'Welcome back.');
                        redirect('admin');
                    }
                    flash('success', 'Welcome back, ' . Auth::user()['name'] . '.');
                    redirect('account');
                }
                $errors['email'] = 'Those credentials do not match our records.';
            }

            remember_old(['email' => $email]);
        }

        View::render('pages/login', [
            'title' => 'Login - RoidsSeek',
            'errors' => $errors,
        ]);
    }

    public function register(): void
    {
        if (Auth::check()) {
            redirect('account');
        }

        $errors = [];
        if (is_post()) {
            verify_csrf();
            $name = trim((string) input('name', ''));
            $email = strtolower(trim((string) input('email', '')));
            $phone = trim((string) input('phone', ''));
            $password = (string) input('password', '');
            $confirm = (string) input('password_confirmation', '');

            if (strlen($name) < 2) {
                $errors['name'] = 'Enter your full name.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Enter a valid email address.';
            } elseif (Database::value('SELECT id FROM users WHERE email = ?', [$email]) !== null) {
                $errors['email'] = 'That email is already registered. Try signing in.';
            }
            if (strlen($password) < 8) {
                $errors['password'] = 'Use at least 8 characters.';
            } elseif ($password !== $confirm) {
                $errors['password_confirmation'] = 'The passwords do not match.';
            }
            if (!input('terms')) {
                $errors['terms'] = 'Please accept the terms of use.';
            }

            if ($errors === []) {
                Auth::register($name, $email, $password, $phone !== '' ? $phone : null);
                clear_old();
                flash('success', 'Your account is ready. Happy shopping!');
                redirect('account');
            }

            remember_old($_POST);
        }

        View::render('pages/register', [
            'title' => 'Join - RoidsSeek',
            'errors' => $errors,
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('');
    }

    public function forgot(): void
    {
        $errors = [];
        $resetLink = null;

        if (is_post()) {
            verify_csrf();
            $email = strtolower(trim((string) input('email', '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Enter a valid email address.';
            } else {
                $user = Database::one('SELECT * FROM users WHERE email = ?', [$email]);
                if ($user !== null) {
                    $token = bin2hex(random_bytes(32));
                    Database::run(
                        'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))',
                        [$email, $token]
                    );
                    // No mail transport is configured for this local build, so the
                    // reset link is displayed directly instead of being emailed.
                    $resetLink = url('reset', ['token' => $token]);
                }
                flash('success', 'If that email exists, a reset link has been generated.');
            }
        }

        View::render('pages/forgot', [
            'title' => 'Forgot Password - RoidsSeek',
            'errors' => $errors,
            'resetLink' => $resetLink,
        ]);
    }

    public function reset(): void
    {
        $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        $reset = Database::one(
            'SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [$token]
        );

        $errors = [];
        $valid = $reset !== null;

        if (is_post() && $valid) {
            verify_csrf();
            $password = (string) input('password', '');
            $confirm = (string) input('password_confirmation', '');

            if (strlen($password) < 8) {
                $errors['password'] = 'Use at least 8 characters.';
            } elseif ($password !== $confirm) {
                $errors['password_confirmation'] = 'The passwords do not match.';
            }

            if ($errors === []) {
                Database::run(
                    'UPDATE users SET password_hash = ? WHERE email = ?',
                    [password_hash($password, PASSWORD_DEFAULT), $reset['email']]
                );
                Database::run('DELETE FROM password_resets WHERE email = ?', [$reset['email']]);
                flash('success', 'Your password has been updated. You can sign in now.');
                redirect('login');
            }
        }

        View::render('pages/reset', [
            'title' => 'Reset Password - RoidsSeek',
            'errors' => $errors,
            'valid' => $valid,
            'token' => $token,
        ]);
    }
}
