<?php

declare(strict_types=1);

namespace App\Support;

/**
 * No-op provider used when no wallet provider/RPC is configured.
 *
 * It reports "not configured" instead of touching the order state, so the
 * automated verifier can still log, expire and rate-limit safely.
 */
final class NoopPaymentProvider implements PaymentProvider
{
    public function canVerify(): bool
    {
        return false;
    }

    public function probe(array $order): array
    {
        return [
            'txHash' => null,
            'receivedSat' => 0,
            'receivedToken' => null,
            'receivedNetwork' => null,
            'confirmations' => 0,
            'error' => 'no_provider_configured',
        ];
    }
}
