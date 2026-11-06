<?php

declare(strict_types=1);

namespace App\Support;

use DateTime;
use DateTimeZone;
use Throwable;

/**
 * Submission and email side of the payment state machine.
 *
 * Handles the idempotent "I have transferred the token" submission and sending
 * the confirmation email exactly when an order becomes paid. Every forced
 * transition goes through {@see Payment::transition()}.
 */
class PaymentSubmission
{
    /**
     * Record that the customer confirmed the transfer.
     *
     * @param array<string, mixed> $order
     * @param string               $txHash  Optional transaction hash (validated by the controller).
     * @param string               $reference Optional reference (validated by the controller).
     * @param string               $actorLabel "Customer" or "Admin" etc.
     *
     * @return string resulting status
     */
    public static function submit(array $order, string $txHash, string $reference, string $actorLabel): string
    {
        $order = Payment::load($order);
        $status = (string) $order['status'];

        if (!in_array($status, ['pending_payment', 'processing', 'payment_submitted'], true)) {
            return $status;
        }

        // Already approved: keep the submission idempotent.
        if (in_array($status, ['processing', 'payment_submitted'], true)) {
            return $status;
        }

        $expiresAt = Payment::expiresAt($order);
        if ($expiresAt !== null && $expiresAt < new DateTime('now', new DateTimeZone('UTC'))) {
            Payment::transition($order, 'expired', 'Payment window expired.', $actorLabel);

            return 'expired';
        }

        $submittedAt = gmdate('Y-m-d H:i:s');
        $pdo = Payment::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE orders SET status = :new_status, payment_tx = IFNULL(payment_tx, :tx_hash),
                 payment_network = :network, payment_token = :token,
                 payment_submitted_at = :submitted_at, updated_at = :updated_at
                 WHERE id = :id AND status = :old_status'
            );

            $stmt->execute([
                // The customer's confirmation approves the order: it is paid as
                // far as they are concerned and goes into processing at once.
                'new_status' => 'processing',
                'tx_hash' => $txHash !== '' ? $txHash : null,
                'network' => self::orderNetwork($order),
                'token' => self::orderToken($order),
                'submitted_at' => $submittedAt,
                'updated_at' => $submittedAt,
                'id' => (int) $order['id'],
                'old_status' => 'pending_payment',
            ]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();

                return (string) Payment::load($order)['status'];
            }

            $reason = $reference !== ''
                ? 'Customer confirmed the transfer (ref: ' . $reference . ')'
                : 'Customer confirmed the transfer';
            Payment::log($order, 'pending_payment', 'processing', $actorLabel, $reason);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return 'processing';
    }

    /** Network recorded on the order, falling back to configuration. */
    public static function orderNetwork(array $order): string
    {
        $network = trim((string) ($order['payment_network'] ?? ''));

        return $network !== '' ? $network : (string) config('payments.network', 'Bitcoin');
    }

    /** Token recorded on the order, falling back to configuration. */
    public static function orderToken(array $order): string
    {
        $token = trim((string) ($order['payment_token'] ?? ''));

        return $token !== '' ? $token : (string) config('payments.token', 'Bitcoin (BTC)');
    }

    /** Send the confirmation email exactly when the order becomes paid. */
    public static function sendConfirmationEmail(array $order): void
    {
        $mailto = $order['email'] ?? null;
        if ($mailto === null || !filter_var($mailto, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subject = sprintf('Order %s confirmed - %s', $order['order_number'], config('app.name', 'RoidsSeek'));

        if (!Mailer::send($mailto, $subject, self::renderConfirmationEmail($order), isset($order['id']) ? (int) $order['id'] : null)) {
            error_log('[payment-email] failed to send to ' . $mailto . ' for order ' . $order['order_number']);
        }
    }

    private static function renderConfirmationEmail(array $order): string
    {
        $lines = [
            'Your order has been confirmed.',
            '',
            'Order number: ' . $order['order_number'],
            'Placed: ' . $order['created_at'],
            'Status: paid',
            '',
            'Items and total are listed below.',
            '',
            'Thank you for your purchase.',
            '',
            'RoidsSeek',
        ];

        return implode(PHP_EOL, $lines);
    }
}
