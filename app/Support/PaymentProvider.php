<?php

declare(strict_types=1);

namespace App\Support;

/** @phpstan-type ProbeResult array{txHash:?string, receivedSat:int, receivedToken:?string, receivedNetwork:?string, confirmations:int, error:?string} */

/**
 * On-chain payment verification provider.
 *
 * Used by {@see Payment::runAutomatedVerification()} to turn a confirmed
 * transfer into an order state change. Inject a configured provider via
 * {@see Payment::setProvider()} (e.g. from a CLI cron or an admin batch run);
 * tests inject a mock provider instead.
 */
interface PaymentProvider
{
    /** True when a blockchain/node provider is configured for this installation. */
    public function canVerify(): bool;

    /**
     * Probe the network for the payment belonging to $order.
     *
     * @param  array<string, mixed>  $order  Order row.
     * @return ProbeResult
     */
    public function probe(array $order): array;
}
