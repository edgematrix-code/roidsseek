<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Database;
use App\Support\Payment;
use App\Support\PaymentAccess;
use App\Support\PaymentMethod;
use App\Support\PaymentSubmission;
use App\Support\RateLimiter;
use App\Support\View;

/**
 * Public payment pages for a pending order.
 *
 * Access is limited to the order owner and to whoever holds the signed guest
 * token issued at checkout (or the browser session that placed the order), so
 * order details are never leaked to a third party.
 */
class PaymentController
{
    public function show(string $orderNumber): void
    {
        $guestToken = (string) input('token', '');
        $order = $this->authorised($orderNumber, $guestToken);

        if ($order === null) {
            $this->notFound();
            return;
        }

        if ((string) $order['status'] === 'paid') {
            $this->redirectTo('checkout/success', (string) $order['order_number'], $guestToken);
            return;
        }

        $items = Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
        $methods = PaymentMethod::all();
        $method = PaymentMethod::get((string) ($order['payment_method'] ?? PaymentMethod::BITCOIN));

        // The customer may switch coin while the order is still awaiting
        // payment; the change is written to the order so support sees the coin
        // that was actually used.
        $requested = trim((string) input('method', ''));
        if ($requested !== '' && PaymentMethod::isValid($requested)) {
            $wanted = PaymentMethod::get($requested);
            if ($wanted['key'] !== $method['key'] && (string) $order['status'] === 'pending_payment') {
                $this->switchMethod($order, $wanted);
                $order = Payment::load($order);
                $method = $wanted;
            }
        }

        // Only trust the address stored on the order when it belongs to the
        // coin being paid; otherwise a stale row could show a Bitcoin amount
        // next to an Ethereum address.
        $storedAddress = trim((string) ($order['payment_address'] ?? ''));
        $address = PaymentMethod::addressFits($method['key'], $storedAddress) ? $storedAddress : '';
        if ($address === '') {
            $address = trim((string) $method['address']);
        }

        $amount = PaymentMethod::amount($order, $method);
        $label = 'Order ' . $order['order_number'];
        $uri = PaymentMethod::uri($method, $address, $amount, $label);
        $expiresAt = (string) ($order['payment_expires_at'] ?? '');

        // Everything around the payment window is computed in UTC on the
        // server: the deadline is a UTC timestamp and the remaining seconds are
        // sent to the page so a skewed client clock cannot change them. The
        // window length is the configured one (not the gap to created_at,
        // which is written by the database and may not even be UTC).
        $expiresTs = $expiresAt !== '' ? (int) strtotime($expiresAt . ' UTC') : 0;
        $windowSeconds = max(60, (int) config('payments.window_minutes', 15) * 60);

        View::render('pages/checkout-payment', [
            'title' => 'Pay order ' . $order['order_number'] . ' - RoidsSeek',
            'seo' => ['title' => 'Pay order ' . $order['order_number'] . ' - RoidsSeek', 'robots' => 'noindex,nofollow'],
            'order' => $order,
            'items' => $items,
            'method' => $method,
            'methods' => $methods,
            'address' => $address,
            'amount' => $amount,
            'uri' => $uri,
            'explorerUrl' => PaymentMethod::explorerUrl($method, (string) ($order['payment_tx'] ?? '')),
            'expiresAt' => $expiresAt,
            'expiresAtTs' => $expiresTs,
            'expiresAtIso' => $expiresTs > 0 ? gmdate('c', $expiresTs) : '',
            'expiresInSeconds' => $expiresTs > 0 ? max(0, $expiresTs - time()) : 0,
            'windowSeconds' => $windowSeconds,
            'guestToken' => $guestToken,
        ]);
    }

    /** Record the coin the customer switched to on an unpaid order. */
    private function switchMethod(array $order, array $method): void
    {
        $rate = (float) $method['usd_rate'];
        $amountSat = $method['key'] === PaymentMethod::BITCOIN
            ? Payment::generateAmountSat((float) $order['total'], $rate > 0 ? (string) $rate : '')
            : 0;

        Database::run(
            'UPDATE orders SET payment_method = ?, payment_address = ?, payment_amount_sat = ?,
             payment_network = ?, payment_token = ?, updated_at = ? WHERE id = ? AND status = ?',
            [
                $method['key'],
                trim((string) $method['address']) !== '' ? trim((string) $method['address']) : null,
                $amountSat,
                (string) $method['network'],
                (string) $method['token'],
                gmdate('Y-m-d H:i:s'),
                (int) $order['id'],
                'pending_payment',
            ]
        );

        Payment::log(
            $order,
            'pending_payment',
            'pending_payment',
            'Customer',
            'Payment method changed to ' . $method['label']
        );
    }

    public function confirm(): void
    {
        if (!is_post()) {
            redirect('checkout');
        }
        verify_csrf();

        $guestToken = (string) input('guest_token', '');
        $order = $this->authorised((string) input('orderId', ''), $guestToken);

        if ($order === null) {
            $this->notFound();
            return;
        }

        $orderNumber = (string) $order['order_number'];

        if (RateLimiter::consume('payment-confirm:' . $this->clientIp())) {
            flash('error', 'Too many attempts. Please wait a minute and try again.');
            $this->redirectTo('checkout/payment', $orderNumber, $guestToken);
            return;
        }

        $txHash = strtolower((string) input('txHash', ''));
        if ($txHash !== '' && preg_match('/^[0-9a-f]{32,128}$/', $txHash) !== 1) {
            flash('error', 'Enter a valid transaction hash (32-128 hex characters).');
            $this->redirectTo('checkout/payment', $orderNumber, $guestToken);
            return;
        }

        $status = PaymentSubmission::submit(
            $order,
            $txHash,
            (string) input('reference', ''),
            'Customer'
        );

        if ($status === 'paid') {
            $this->redirectTo('checkout/success', $orderNumber, $guestToken);
            return;
        }

        if ($status === 'expired') {
            flash('error', 'The payment window for this order has expired. Please place a new order.');
            $this->redirectTo('checkout/payment', $orderNumber, $guestToken);
            return;
        }

        if (in_array($status, ['processing', 'payment_submitted'], true)) {
            flash('success', 'Payment approved - your order is now processing. We will confirm the transfer on-chain before dispatch.');
            $this->redirectTo('checkout/payment', $orderNumber, $guestToken);
            return;
        }

        flash('error', 'This order is no longer awaiting payment.');
        $this->redirectTo('checkout/payment', $orderNumber, $guestToken);
    }

    public function success(string $orderNumber): void
    {
        $guestToken = (string) input('token', '');
        $order = $this->authorised($orderNumber, $guestToken);

        if ($order === null) {
            $this->notFound();
            return;
        }

        if ((string) $order['status'] !== 'paid') {
            $this->redirectTo('checkout/payment', (string) $order['order_number'], $guestToken);
            return;
        }

        View::render('pages/checkout-success', [
            'title' => 'Order ' . $order['order_number'] . ' confirmed - RoidsSeek',
            'seo' => ['title' => 'Order ' . $order['order_number'] . ' confirmed - RoidsSeek', 'robots' => 'noindex,nofollow'],
            'order' => $order,
            'items' => Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]),
            'guestToken' => $guestToken,
        ]);
    }

    /** The order row when this request may see it, null otherwise. */
    private function authorised(string $orderNumber, string $guestToken): ?array
    {
        if ($orderNumber === '') {
            return null;
        }

        return PaymentAccess::check($orderNumber, $guestToken !== '' ? $guestToken : null);
    }

    private function redirectTo(string $route, string $orderNumber, string $guestToken): void
    {
        $params = ['orderId' => $orderNumber];
        if ($guestToken !== '') {
            $params['token'] = $guestToken;
        }

        redirect($route, $params);
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('pages/404', ['title' => 'Order not found - RoidsSeek']);
    }

    /** Rate-limit key for the caller; local addresses share one bucket. */
    private function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return preg_match('/^(?:10\.|172\.(?:1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $ip) ? 'local' : $ip;
    }
}
