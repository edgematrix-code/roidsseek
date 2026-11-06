<?php

declare(strict_types=1);

namespace App\Support;

use DateTime;
use DateTimeZone;
use Throwable;

/**
 * Automated on-chain verification loop and admin-facing manual transitions.
 *
 * {@see PaymentVerification::runAutomatedVerification()} is the idempotent
 * entry point for a cron/batch job that probes the network for each order.
 */
class PaymentVerification
{
    /** Automatic verification (cron / batch). */
    public static function runAutomatedVerification(array $order): string
    {
        $order = Payment::load($order);
        $status = (string) $order['status'];

        if (!in_array($status, ['pending_payment', 'processing', 'payment_submitted'], true)) {
            return $status;
        }

        $expiresAt = Payment::expiresAt($order);
        if ($expiresAt !== null && $expiresAt < new DateTime('now', new DateTimeZone('UTC'))) {
            Payment::transition($order, 'expired', 'Payment window expired.', 'system');
            return 'expired';
        }

        $provider = Payment::provider();
        if (!$provider->canVerify()) {
            Payment::log($order, $status, $status, 'system', 'Automated verification skipped: no blockchain provider configured');
            return $status;
        }

        $receipt = $provider->probe($order);
        $outcome = PaymentDecision::assess($order, $receipt);

        $pdo = Payment::pdo();
        $pdo->beginTransaction();

        try {
            switch ($outcome['outcome']) {
                case 'paid':
                    $stmt = $pdo->prepare(
                        'UPDATE orders SET status = :new_status, payment_tx = IFNULL(payment_tx, :tx_hash),
                         payment_verified_at = :verified_at, updated_at = :updated_at
                         WHERE id = :id AND status = :old_status'
                    );
                    $stmt->execute([
                        'new_status' => 'paid',
                        'tx_hash' => $receipt['txHash'] ?? null,
                        'verified_at' => gmdate('Y-m-d H:i:s'),
                        'updated_at' => gmdate('Y-m-d H:i:s'),
                        'id' => (int) $order['id'],
                        'old_status' => $status,
                    ]);

                    if ($stmt->rowCount() > 0) {
                        Payment::log($order, $status, 'paid', 'system', 'Automated on-chain verification passed');
                        $pdo->commit();
                        PaymentSubmission::sendConfirmationEmail($order);
                    } else {
                        $pdo->commit();
                    }
                    break;

                case 'failed':
                case 'expired':
                    $stmt = $pdo->prepare(
                        'UPDATE orders SET status = :new_status, payment_tx = IFNULL(payment_tx, :tx_hash), updated_at = :updated_at
                         WHERE id = :id AND status = :old_status'
                    );
                    $stmt->execute([
                        'new_status' => $outcome['outcome'],
                        'tx_hash' => $receipt['txHash'] ?? null,
                        'updated_at' => gmdate('Y-m-d H:i:s'),
                        'id' => (int) $order['id'],
                        'old_status' => $status,
                    ]);

                    if ($stmt->rowCount() > 0) {
                        Payment::log($order, $status, $outcome['outcome'], 'system', $outcome['reason'] ?? 'Automated verification outcome');
                    }
                    $pdo->commit();
                    break;

                case 'confirmations_pending':
                case 'no_change':
                default:
                    $pdo->commit();
                    break;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return (string) Payment::load($order)['status'];
    }

    /**
     * Manual confirmation by an administrator.
     *
     * Only this path (or the automated verification path) may mark an order
     * paid.
     */
    public static function manualConfirm(array $order, string $actorLabel): string
    {
        $order = Payment::load($order);
        if (!in_array((string) $order['status'], ['processing', 'payment_submitted'], true)) {
            return (string) $order['status'];
        }

        Payment::transition($order, 'paid', 'Manual confirmation by ' . $actorLabel, $actorLabel);

        $order = Payment::load($order);
        PaymentSubmission::sendConfirmationEmail($order);

        return 'paid';
    }

    /**
     * Manual rejection by an administrator (wrong token, duplicate, fraud, ...).
     */
    public static function manualReject(array $order, string $reason, string $actorLabel): string
    {
        $order = Payment::load($order);
        if (!in_array((string) $order['status'], ['processing', 'payment_submitted', 'failed'], true)) {
            return (string) $order['status'];
        }

        Payment::transition($order, 'failed', $reason, $actorLabel);

        return 'failed';
    }
}
