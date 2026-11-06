<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Cart;
use App\Support\Database;
use App\Support\Payment;
use App\Support\PaymentAccess;
use App\Support\PaymentMethod;
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

        // Price the basket for the country already entered (after a validation
        // error) so the summary reflects the same delivery rate the order will
        // be charged.
        $country = (string) input('shipping_country', '');

        View::render('pages/checkout', [
            'title' => 'Checkout - RoidsSeek',
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
            'discount' => Cart::discount(),
            'shipping' => Cart::shipping($country),
            'total' => Cart::total($country),
            'coupon' => Cart::coupon(),
            'freeShipping' => Cart::qualifiesForFreeShipping(),
            'shippingRates' => Cart::shippingRates(),
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

        // The promo form on the cart and checkout pages posts here. Apply the
        // code before validating the address so the customer sees the discount
        // reflected in the order summary instead of losing the code.
        if (isset($_POST['apply_discount'])) {
            [$ok, $message] = Cart::applyCoupon((string) input('code', ''));
            flash($ok ? 'success' : 'error', $message);
            remember_old($_POST);
            redirect('checkout');
        }

        // Legacy values (crypto/bank_transfer/cash) still complete in-flight
        // forms but are recorded as Bitcoin, as before.
        $rawPayment = (string) input('payment_method', PaymentMethod::BITCOIN);
        $legacyPayment = in_array($rawPayment, ['crypto', 'bank_transfer', 'cash'], true);

        $data = [
            'email' => strtolower(trim((string) input('email', ''))),
            'name' => trim((string) input('shipping_name', '')),
            'phone' => trim((string) input('shipping_phone', '')),
            'address' => trim((string) input('shipping_address', '')),
            'city' => trim((string) input('shipping_city', '')),
            'state' => trim((string) input('shipping_state', '')),
            'postal' => trim((string) input('shipping_postal', '')),
            'country' => trim((string) input('shipping_country', '')),
            'payment' => $legacyPayment ? PaymentMethod::BITCOIN : PaymentMethod::normalise($rawPayment),
            'notes' => trim((string) input('notes', '')),
        ];

        $method = PaymentMethod::get($data['payment']);

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
        if (!PaymentMethod::isValid($rawPayment) && !$legacyPayment) {
            $errors['payment_method'] = 'Choose a valid payment method.';
        }

        if ($errors !== []) {
            $this->showCheckoutWithErrors($errors);
            return;
        }

        if (!Cart::meetsMinimumOrder()) {
            flash('error', sprintf(
                'Your order must be at least %s. Add %s more to check out.',
                money(Cart::minimumOrder()),
                money(Cart::minimumOrderShortfall())
            ));
            remember_old($_POST);
            redirect('checkout');
        }

        $items = Cart::items();
        $subtotal = Cart::subtotal();
        $discount = Cart::discount();
        // The entered country decides the delivery service: a US address is
        // always charged the domestic rate, and the promo code removes it.
        $shipping = Cart::shipping($data['country']);
        $total = Cart::total($data['country']);
        $coupon = Cart::coupon();

        // Re-check the live price and active flag at placement time, not only
        // when the item was added. Every priced product stays sellable, so the
        // stock counter is not a gate here.
        foreach ($items as $row) {
            $fresh = Database::one(
                'SELECT price, is_active FROM products WHERE id = ?',
                [(int) $row['product']['id']]
            );
            if ($fresh === null
                || (int) $fresh['is_active'] !== 1
                || (float) $fresh['price'] < min_product_price()) {
                flash('error', 'Some items are no longer available. Please review your cart.');
                redirect('cart');
            }
        }

        $orderNumber = 'RS-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $windowMinutes = max(1, (int) config('payments.window_minutes', 15));
        $paymentExpiresAt = gmdate('Y-m-d H:i:s', time() + ($windowMinutes * 60));
        $paymentAddress = trim((string) $method['address']);
        $rate = (float) $method['usd_rate'];
        $amountSat = $method['key'] === PaymentMethod::BITCOIN
            ? Payment::generateAmountSat($total, $rate > 0 ? (string) $rate : '')
            : 0;

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();

            $orderId = Database::insert(
                'INSERT INTO orders (order_number, user_id, email, status, subtotal, discount, shipping_total, total,
                    coupon_code, payment_method, payment_address, payment_amount_sat, payment_network, payment_token,
                    payment_expires_at, shipping_name, shipping_phone, shipping_address, shipping_city,
                    shipping_state, shipping_postal, shipping_country, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $orderNumber,
                    Auth::id(),
                    $data['email'],
                    'pending_payment',
                    $subtotal,
                    $discount,
                    $shipping,
                    $total,
                    $coupon['code'] ?? null,
                    $method['key'],
                    $paymentAddress !== '' ? $paymentAddress : null,
                    $amountSat,
                    (string) $method['network'],
                    (string) $method['token'],
                    $paymentExpiresAt,
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
                    'UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?',
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

        // Send the customer straight to the payment page, where the address,
        // amount and countdown for the chosen coin are shown.
        $guestToken = PaymentAccess::signGuestToken(
            $orderId,
            $data['email'],
            max(1, (int) config('payments.guest_ttl_minutes', 1440))
        );
        $params = ['orderId' => $orderNumber];
        if ($guestToken !== '') {
            $params['token'] = $guestToken;
        }

        redirect('checkout/payment', $params);
    }

    /** Re-render checkout after a validation error, keeping the entered values. */
    private function showCheckoutWithErrors(array $errors): void
    {
        remember_old($_POST);
        $country = (string) input('shipping_country', '');

        View::render('pages/checkout', [
            'title' => 'Checkout - RoidsSeek',
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
            'discount' => Cart::discount(),
            'shipping' => Cart::shipping($country),
            'total' => Cart::total($country),
            'coupon' => Cart::coupon(),
            'freeShipping' => Cart::qualifiesForFreeShipping(),
            'shippingRates' => Cart::shippingRates(),
            'errors' => $errors,
        ]);
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
