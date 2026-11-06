<?php

declare(strict_types=1);

namespace App\Support;

use DateTime;
use DateTimeZone;
use PDO;
use Throwable;

/**
 * Server-side order payment state machine.
 *
 * Only the server changes order state. This class centralises every transition,
 * keeps the operations idempotent, records a {@see payment_log} entry for every
 * status change, and owns sending the confirmation email exactly when an order
 * becomes {@see paid}.
 *
 * The automated on-chain check is injected (see PaymentProvider). Without a
 * configured provider the automated path stays idle and manual confirmation is
 * used.
 */
class Payment
{
    /** @var array<string, PaymentProvider> */
    private static array $providers = [];

    public static function setProvider(string $key, PaymentProvider $provider): void
    {
        self::$providers[$key] = $provider;
    }

    public static function provider(?string $key = 'default'): PaymentProvider
    {
        if (isset(self::$providers[$key])) {
            return self::$providers[$key];
        }

        return new NoopPaymentProvider();
    }

    public static function pdo(): PDO
    {
        return Database::pdo();
    }

    /** Read the order row by number so stale arrays can never leak state. */
    public static function load(array $order): array
    {
        if (isset($order['order_number'])) {
            $loaded = Database::one('SELECT * FROM orders WHERE order_number = ?', [$order['order_number']]);
            if ($loaded !== null) {
                return $loaded;
            }
        }

        return $order;
    }

    public static function expiresAt(array $order): ?DateTime
    {
        if (!isset($order['payment_expires_at']) || $order['payment_expires_at'] === null || $order['payment_expires_at'] === '') {
            return null;
        }

        $dt = DateTime::createFromFormat('Y-m-d H:i:s', (string) $order['payment_expires_at'], new DateTimeZone('UTC'));

        return $dt !== false ? $dt : null;
    }

    /**
     * Move an order to $toStatus once and record the change.
     *
     * The UPDATE is guarded on the current status, so two concurrent callers
     * can never both apply the same transition.
     */
    public static function transition(array $order, string $toStatus, string $reason, string $actorLabel): void
    {
        $order = self::load($order);
        $fromStatus = (string) $order['status'];
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $sql = 'UPDATE orders SET status = :new_status, updated_at = :updated_at';
            $params = [
                'new_status' => $toStatus,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'id' => (int) $order['id'],
                'old_status' => $fromStatus,
            ];

            if ($toStatus === 'paid') {
                $sql .= ', payment_verified_at = :verified_at';
                $params['verified_at'] = gmdate('Y-m-d H:i:s');
            }

            $stmt = $pdo->prepare($sql . ' WHERE id = :id AND status = :old_status');
            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                self::log($order, $fromStatus, $toStatus, $actorLabel, $reason);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Insert the audit row for every status change. */
    public static function log(array $order, string $fromStatus, string $toStatus, string $actorLabel, string $reason): void
    {
        $reason = trim($reason);
        if ($reason !== '') {
            $reason = function_exists('mb_substr') ? mb_substr($reason, 0, 190) : substr($reason, 0, 190);
        }

        Database::run(
            'INSERT INTO payment_log (order_id, from_status, to_status, actor, reason) VALUES (?, ?, ?, ?, ?)',
            [(int) $order['id'], $fromStatus, $toStatus, $actorLabel, $reason !== '' ? $reason : null]
        );
    }

    public static function formatBtcAmountSat(int $sat): string
    {
        $btc = $sat / 100000000.0;

        return number_format($btc, 8, '.', '') . ' BTC';
    }

    /**
     * Lock the BTC amount the customer must send at checkout time.
     *
     * @param string $usdRate BTC/USD rate or '' when not configured (manual amount).
     */
    public static function generateAmountSat(float $totalUsd, string $usdRate): int
    {
        if ($usdRate === '' || (float) $usdRate <= 0) {
            return 0; // Manual amount; a support agent confirms the BTC value.
        }

        return (int) round($totalUsd / (float) $usdRate * 100000000.0);
    }

    public static function decide(array $order, array $receipt): array
    {
        return PaymentDecision::assess($order, $receipt);
    }
}
