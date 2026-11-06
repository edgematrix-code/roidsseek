<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pure outcome assessment for a payment receipt.
 *
 * This class never writes to the database. It is the sole place that decides
 * whether a transfer is underpaid, overpaid, a duplicate, wrong token/network,
 * or ready to be marked {@see App\Support\Payment::SUBMITTED} / {@see paid}.
 * Unit tests exercise it directly.
 */
final class PaymentDecision
{
    /**
     * Decide the outcome for $order given an on-chain $receipt.
     *
     * @param array<string, mixed> $order    Order row.
     * @param array<string, mixed> $receipt  Probe result from a PaymentProvider.
     * @return array{outcome:string, reason:?string}
     */
    public static function assess(array $order, array $receipt): array
    {
        $requiredSat = (int) $order['payment_amount_sat'];
        $tolerance = max(1, (int) round($requiredSat * 0.0001));

        $txHash = $receipt['txHash'] ?? null;
        $receivedSat = $receipt['receivedSat'] ?? null;
        $confirmations = (int) ($receipt['confirmations'] ?? 0);
        $expectedToken = (string) $order['payment_token'];
        $expectedNetwork = (string) $order['payment_network'];
        $receivedToken = $receipt['token'] ?? null;
        $receivedNetwork = $receipt['network'] ?? null;
        $orderId = (int) $order['id'];

        if (!is_numeric($receivedSat) || (int) $receivedSat < 0) {
            return ['outcome' => 'no_change', 'reason' => 'invalid_received_amount'];
        }

        // Duplicate transaction hash on another order is a hard rejection.
        if ($txHash !== null && $txHash !== '' && $txHash !== strval($order['payment_tx'])) {
            $dup = Database::one('SELECT id FROM orders WHERE payment_tx = ? AND id <> ?', [$txHash, $orderId]);
            if ($dup !== null) {
                return ['outcome' => 'duplicate_tx', 'reason' => 'duplicate_tx_hash_on_another_order'];
            }
        }

        if ($receivedToken !== null && $receivedToken !== '' && strcasecmp((string) $receivedToken, $expectedToken) !== 0) {
            return ['outcome' => 'wrong_token', 'reason' => 'token_does_not_match'];
        }

        if ($receivedNetwork !== null && $receivedNetwork !== '' && strcasecmp((string) $receivedNetwork, $expectedNetwork) !== 0) {
            return ['outcome' => 'wrong_network', 'reason' => 'network_does_not_match'];
        }

        if ($confirmations > 0 && $confirmations < max(1, (int) config('payments.btc.confirmations', 2))) {
            return ['outcome' => 'confirmations_pending', 'reason' => 'confirmations_below_required'];
        }

        if ((int) $receivedSat < $requiredSat - $tolerance) {
            return ['outcome' => 'underpaid', 'reason' => 'received_below_required_amount'];
        }

        if ((int) $receivedSat > $requiredSat + $tolerance) {
            return ['outcome' => 'overpaid', 'reason' => 'received_above_required_amount_accepted'];
        }

        return ['outcome' => 'paid', 'reason' => 'amount_matches'];
    }
}
