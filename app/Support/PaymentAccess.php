<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Access control for the public payment pages.
 *
 * The payment page is limited to the owner of the order (the logged-in user
 * whose user_id matches), the browser session that placed the order, and a
 * guest holding the signed token issued at checkout. Anyone else receives a
 * 404 so that order existence is not leaked.
 */
class PaymentAccess
{
    /**
     * Authorise a request to view or confirm a payment page.
     *
     * @param  array<string, mixed>  $order  Row from the `orders` table.
     * @return array<string, mixed>|null     The order row when authorised, null otherwise.
     */
    public static function check(string $orderNumber, ?string $guestToken = null): ?array
    {
        $order = Database::one('SELECT * FROM orders WHERE order_number = ?', [$orderNumber]);

        if ($order === null) {
            return null;
        }

        // Logged-in owner.
        $userId = Auth::id();
        if ($userId !== null && isset($order['user_id']) && (int) $order['user_id'] === (int) $userId) {
            return $order;
        }

        // Browser session that placed the order. Works without a guest secret.
        if (($_SESSION['last_order'] ?? null) === $orderNumber) {
            return $order;
        }

        // Guest token.
        if ($guestToken !== null && isset($order['email'])) {
            if (self::verifyGuestToken($guestToken, (int) $order['id'], (string) $order['email'])) {
                return $order;
            }
        }

        return null;
    }

    /** Generate a signed token for a guest who created the order. */
    public static function signGuestToken(int $orderId, string $email, int $ttlMinutes = 1440): string
    {
        $payload = json_encode(
            [
                'id' => $orderId,
                'email' => $email,
                'exp' => time() + ($ttlMinutes * 60),
            ],
            JSON_THROW_ON_ERROR
        );
        $secret = (string) config('payments.guest_secret', '');
        if ($secret === '') {
            // No secret configured: the payment page stays reachable from the
            // browser session that placed the order, but no portable link exists.
            return '';
        }

        // The signature covers the encoded payload exactly as it travels in the
        // URL; verifyGuestToken() re-hashes that same string.
        $encoded = rtrim(base64_encode($payload), '=');

        return $encoded . '.' . hash_hmac('sha256', $encoded, $secret);
    }

    /** Verify a guest token without trusting the session. */
    public static function verifyGuestToken(string $token, int $orderId, string $email): bool
    {
        if ($token === '' || strpos($token, '.') === false) {
            return false;
        }

        [$payload, $signature] = explode('.', $token, 2);

        $secret = (string) config('payments.guest_secret', '');
        if ($secret === '') {
            return false;
        }

        if (strlen($signature) !== 64 || !hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) {
            return false;
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        $data = json_decode($decoded, true);

        if (!is_array($data)
            || !isset($data['id'], $data['email'], $data['exp'])
            || !is_string($data['email'])
            || $data['exp'] < time()
        ) {
            return false;
        }

        if ((int) $data['id'] !== $orderId || strcasecmp($data['email'], $email) !== 0) {
            return false;
        }

        return true;
    }
}
