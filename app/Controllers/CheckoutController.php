<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Cart;
use App\Support\Database;
use App\Support\View;
use Throwable;

class CheckoutController
{
    public function index(): void
    {
        if (Cart::isEmpty()) {
            flash('error', 'Your cart is empty.');
            redirect('cart');
        }

        View::render('pages/checkout', [
            'title' => 'Checkout - RoidsSeek',
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
            'discount' => Cart::discount(),
            'shipping' => Cart::shipping(),
            'total' => Cart::total(),
            'coupon' => Cart::coupon(),
            'errors' => [],
        ]);
    }

    public function place(): void
    {
        if (!is_post()) {
            redirect('checkout');
        }
        verify_csrf();

        if (Cart::isEmpty()) {
            flash('error', 'Your cart is empty.');
            redirect('cart');
        }

        $data = [
            'email' => strtolower(trim((string) input('email', ''))),
            'name' => trim((string) input('shipping_name', '')),
            'phone' => trim((string) input('shipping_phone', '')),
            'address' => trim((string) input('shipping_address', '')),
            'city' => trim((string) input('shipping_city', '')),
            'state' => trim((string) input('shipping_state', '')),
            'postal' => trim((string) input('shipping_postal', '')),
            'country' => trim((string) input('shipping_country', '')),
            'payment' => (string) input('payment_method', 'bank_transfer'),
            'notes' => trim((string) input('notes', '')),
        ];

        $errors = [];
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($data['name'] === '') {
            $errors['shipping_name'] = 'Enter the recipient name.';
        }
        if ($data['address'] === '') {
            $errors['shipping_address'] = 'Enter a shipping address.';
        }
        if ($data['country'] === '') {
            $errors['shipping_country'] = 'Enter a country.';
        }
        if (!in_array($data['payment'], ['bank_transfer', 'crypto', 'cash'], true)) {
            $errors['payment_method'] = 'Choose a payment method.';
        }

        if ($errors !== []) {
            remember_old($_POST);
            View::render('pages/checkout', [
                'title' => 'Checkout - RoidsSeek',
                'items' => Cart::items(),
                'subtotal' => Cart::subtotal(),
                'discount' => Cart::discount(),
                'shipping' => Cart::shipping(),
                'total' => Cart::total(),
                'coupon' => Cart::coupon(),
                'errors' => $errors,
            ]);
            return;
        }

        $items = Cart::items();
        $subtotal = Cart::subtotal();
        $discount = Cart::discount();
        $shipping = Cart::shipping();
        $total = Cart::total();
        $coupon = Cart::coupon();
        $orderNumber = 'RS-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();

            $orderId = Database::insert(
                'INSERT INTO orders (order_number, user_id, email, status, subtotal, discount, shipping_total, total,
                    coupon_code, payment_method, shipping_name, shipping_phone, shipping_address, shipping_city,
                    shipping_state, shipping_postal, shipping_country, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $orderNumber,
                    Auth::id(),
                    $data['email'],
                    'pending',
                    $subtotal,
                    $discount,
                    $shipping,
                    $total,
                    $coupon['code'] ?? null,
                    $data['payment'],
                    $data['name'],
                    $data['phone'],
                    $data['address'],
                    $data['city'],
                    $data['state'],
                    $data['postal'],
                    $data['country'],
                    $data['notes'],
                ]
            );

            foreach ($items as $row) {
                $product = $row['product'];
                Database::run(
                    'INSERT INTO order_items (order_id, product_id, name, sku, unit_price, qty, total)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$orderId, $product['id'], $product['name'], $product['sku'], $row['unit_price'], $row['qty'], $row['line_total']]
                );

                Database::run(
                    'UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?',
                    [$row['qty'], $product['id']]
                );
            }

            if ($coupon !== null) {
                Database::run('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$coupon['id']]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'We could not place the order. Please try again.');
            redirect('checkout');
        }

        Cart::clear();
        unset($_SESSION['_old']);
        $_SESSION['last_order'] = $orderNumber;

        redirect('order', ['number' => $orderNumber]);
    }

    public function confirmation(string $number): void
    {
        $order = Database::one('SELECT * FROM orders WHERE order_number = ?', [$number]);
        if ($order === null) {
            http_response_code(404);
            View::render('pages/404', ['title' => 'Order not found - RoidsSeek']);
            return;
        }

        $allowed = ($_SESSION['last_order'] ?? null) === $number
            || (Auth::check() && (int) $order['user_id'] === Auth::id())
            || Auth::isAdmin();

        if (!$allowed) {
            flash('error', 'Please sign in to view that order.');
            redirect('login');
        }

        $items = Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);

        View::render('pages/order', [
            'title' => 'Order ' . $order['order_number'] . ' - RoidsSeek',
            'order' => $order,
            'items' => $items,
            'justPlaced' => ($_SESSION['last_order'] ?? null) === $number,
        ]);
    }
}
