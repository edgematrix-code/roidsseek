<?php
/**
 * @var array $order @var array $items @var array $method @var array $methods
 * @var string $address @var string|null $amount @var string $uri @var string $explorerUrl
 */
$approved = in_array((string) $order['status'], ['processing', 'payment_submitted'], true);
$expired = (string) $order['status'] === 'expired';
$failed = (string) $order['status'] === 'failed';
$payable = (string) $order['status'] === 'pending_payment';

$itemCount = 0;
foreach ($items as $line) {
    $itemCount += (int) $line['qty'];
}

$coinAmount = $amount !== null ? $amount . ' ' . $method['symbol'] : '';
$placedTs = strtotime((string) $order['created_at'] . ' UTC');
$statusLabel = $approved ? 'Approved' : ucfirst(str_replace('_', ' ', (string) $order['status']));
$statusTone = $approved ? 'ok' : ($expired || $failed ? 'danger' : 'warn');
$remaining = max(0, (int) ($expiresInSeconds ?? 0));
$window = max(60, (int) ($windowSeconds ?? 900));
?>
<div class="ckp">
    <div class="ckp-shell">
        <header class="ckp-header">
            <div>
                <h1 class="ckp-title">Complete your payment</h1>
                <p class="ckp-sub">
                    Order <strong><?= e($order['order_number']) ?></strong> &middot;
                    <?= (int) $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?> &middot;
                    <?= e($method['label']) ?> on <?= e($method['network']) ?>
                </p>
            </div>
            <span class="ckp-secure">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <rect x="4" y="10" width="16" height="10" rx="2.5"></rect>
                    <path d="M8 10V7.5a4 4 0 0 1 8 0V10"></path>
                </svg>
                Secure checkout
            </span>
        </header>

        <ol class="ckp-progress" aria-label="Checkout progress">
            <li class="is-complete">
                <span class="ckp-progress__dot" aria-hidden="true">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                </span>
                Order
            </li>
            <li class="is-active" aria-current="step">
                <span class="ckp-progress__dot" aria-hidden="true">2</span>
                Payment
            </li>
            <li>
                <span class="ckp-progress__dot" aria-hidden="true">3</span>
                Confirmation
            </li>
        </ol>

        <div class="ckp-layout">
            <div class="ckp-main">
                <?php if ($approved): ?>
                    <section class="ckp-card ckp-card--approved" aria-labelledby="ckp-approved-title">
                        <div class="ckp-approved__head">
                            <span class="ckp-approved__check" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                            </span>
                            <div>
                                <h2 id="ckp-approved-title" class="ckp-card__title">Approved and processing</h2>
                                <p class="ckp-approved__text">
                                    We recorded your <?= e($method['label']) ?> transfer notice for order
                                    <strong><?= e($order['order_number']) ?></strong>. The order is now processing and is
                                    confirmed on-chain before dispatch.
                                </p>
                            </div>
                        </div>

                        <dl class="ckp-facts">
                            <div>
                                <dt>Method</dt>
                                <dd>
                                    <span class="ckp-facts__coin"><?= \App\Support\PaymentMethod::icon($method['key']) ?></span>
                                    <?= e($method['label']) ?> (<?= e($method['symbol']) ?>)
                                </dd>
                            </div>
                            <div>
                                <dt>Network</dt>
                                <dd><?= e($method['network']) ?></dd>
                            </div>
                            <?php if (!empty($order['payment_tx'])): ?>
                                <div>
                                    <dt>Transaction</dt>
                                    <dd>
                                        <code class="ckp-facts__hash" id="ckp-tx"><?= e($order['payment_tx']) ?></code>
                                        <?php if ($explorerUrl !== ''): ?>
                                            <a class="ckp-facts__link" href="<?= e($explorerUrl) ?>" target="_blank" rel="noopener noreferrer">View on explorer</a>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                            <div>
                                <dt>Total</dt>
                                <dd><?= money($order['total']) ?></dd>
                            </div>
                        </dl>

                        <div class="ckp-actions">
                            <a class="ckp-btn ckp-btn--primary" href="<?= e(url('order', ['number' => $order['order_number']])) ?>">View order</a>
                            <a class="ckp-btn" href="<?= e(url('all')) ?>">Continue shopping</a>
                        </div>
                    </section>
                <?php else: ?>
                    <?php if ($expired || $failed): ?>
                        <div class="ckp-banner ckp-banner--danger" role="status">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 8v5M12 16h.01"></path>
                            </svg>
                            <div>
                                <strong><?= $expired ? 'The payment window has expired' : 'This payment could not be verified' ?></strong>
                                <p>
                                    <?= $expired
                                        ? 'Please place a new order to pay again. Nothing was charged.'
                                        : 'Please contact support quoting your order number before sending anything else.' ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <section class="ckp-card" aria-labelledby="ckp-method-title">
                        <h2 id="ckp-method-title" class="ckp-card__title">Payment method</h2>
                        <div class="ckp-methods">
                            <?php foreach ($methods as $key => $option): ?>
                                <?php $active = $key === $method['key']; ?>
                                <a class="ckp-method<?= $active ? ' is-active' : '' ?>"
                                   href="<?= e(url('checkout/payment', array_filter([
                                       'orderId' => $order['order_number'],
                                       'token' => $guestToken !== '' ? $guestToken : null,
                                       'method' => $key,
                                   ]))) ?>"
                                   aria-current="<?= $active ? 'true' : 'false' ?>">
                                    <span class="ckp-method__icon"><?= \App\Support\PaymentMethod::icon($key) ?></span>
                                    <span class="ckp-method__text">
                                        <strong><?= e($option['label']) ?></strong>
                                        <span><?= e($option['symbol']) ?> &middot; <?= e($option['network']) ?></span>
                                    </span>
                                    <span class="ckp-method__check" aria-hidden="true">
                                        <svg class="icon" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="ckp-card" aria-labelledby="ckp-transfer-title">
                        <div class="ckp-card__head">
                            <h2 id="ckp-transfer-title" class="ckp-card__title">Amount to send</h2>
                            <span class="ckp-badge"><?= e($method['network']) ?> network</span>
                        </div>

                        <?php if ($address === ''): ?>
                            <div class="ckp-banner ckp-banner--warn" role="alert">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 8v5M12 16h.01"></path>
                                </svg>
                                <div>
                                    <strong><?= e($method['label']) ?> payment details are not configured yet</strong>
                                    <p>Contact support and quote order <strong><?= e($order['order_number']) ?></strong> before sending any payment.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="ckp-amount">
                                <p class="ckp-amount__main" aria-live="off">
                                    <?= $coinAmount !== '' ? e($coinAmount) : e(money($order['total'])) ?>
                                </p>
                                <p class="ckp-amount__sub">
                                    <?php if ($coinAmount !== '') : ?>
                                        Equals <?= e(money($order['total'])) ?> at
                                        <?= e(number_format((float) $method['usd_rate'], 2, '.', ',')) ?>
                                        <?= e(\App\Support\Site::currency() ?: 'USD') ?>/<?= e($method['symbol']) ?>
                                    <?php else: ?>
                                        Send the equivalent in <?= e($method['symbol']) ?> &middot; total due <?= e(money($order['total'])) ?>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <?php if ($coinAmount === ''): ?>
                                <div class="ckp-callout">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="M12 11v5M12 8h.01"></path>
                                    </svg>
                                    <span>
                                        Support confirms the exact <?= e($method['symbol']) ?> amount for order
                                        <strong><?= e($order['order_number']) ?></strong> before you send.
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="ckp-address">
                                <div class="ckp-address__body">
                                    <span class="ckp-address__label"><?= e($method['label']) ?> receive address</span>
                                    <code class="ckp-address__value" id="ckp-address"><?= e($address) ?></code>
                                    <div class="ckp-address__actions">
                                        <button type="button" class="ckp-btn ckp-btn--copy" data-copy="#ckp-address">
                                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                                <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                                <path d="M5 15V6a2 2 0 0 1 2-2h9"></path>
                                            </svg>
                                            Copy address
                                        </button>
                                        <?php if ($uri !== ''): ?>
                                            <a class="ckp-btn" href="<?= e($uri) ?>" rel="nofollow">Open in <?= e($method['label']) ?> wallet</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($method['qr_image'])): ?>
                                <div class="ckp-qr ckp-qr--image">
                                    <img src="<?= e(asset($method['qr_image'])) ?>" alt="<?= e($method['label']) ?> QR code" class="ckp-qr__img">
                                </div>
                                <?php else: ?>
                                <div class="ckp-qr"
                                     data-qr="<?= e($address) ?>"
                                     data-qr-label="<?= e($method['label']) ?> payment address QR code">
                                    <span class="ckp-qr__placeholder" aria-hidden="true">QR</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="ckp-alert" role="note">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 3 2.5 20h19z"></path>
                                    <path d="M12 9v5M12 17h.01"></path>
                                </svg>
                                <div>
                                    <strong>Send only <?= e($method['label']) ?> (<?= e($method['symbol']) ?>) on the <?= e($method['network']) ?> network.</strong>
                                    <p>Any other asset or network sent to this address cannot be recovered.</p>
                                </div>
                            </div>

                            <?php if ($payable && $expiresAt !== ''): ?>
                                <div class="ckp-countdown"
                                     data-countdown
                                     data-total-seconds="<?= (int) $window ?>"
                                     data-remaining-seconds="<?= (int) $remaining ?>"
                                     data-expires-at="<?= e($expiresAtIso ?? '') ?>">
                                    <div class="ckp-countdown__head">
                                        <span class="ckp-countdown__label">
                                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M12 7v5l3 2"></path>
                                            </svg>
                                            Payment window
                                        </span>
                                        <strong class="ckp-countdown__time" data-countdown-label><?= (int) floor($remaining / 60) ?>:<?= str_pad((string) ($remaining % 60), 2, '0', STR_PAD_LEFT) ?></strong>
                                    </div>
                                    <div class="ckp-countdown__track" aria-hidden="true">
                                        <span class="ckp-countdown__bar" data-countdown-bar style="width:<?= $window > 0 ? min(100, round($remaining / $window * 100)) : 100 ?>%"></span>
                                    </div>
                                    <p class="ckp-countdown__meta">
                                        Closes <?= e(gmdate('M j, Y H:i', (int) $expiresAtTs)) ?> UTC &middot;
                                        <?= (int) $method['confirmations'] ?> network confirmation<?= (int) $method['confirmations'] === 1 ? '' : 's' ?> required
                                    </p>
                                    <p class="ckp-sr" aria-live="polite" data-countdown-live></p>
                                </div>
                            <?php endif; ?>

                            <h3 class="ckp-steps-title">How to pay</h3>
                            <ol class="ckp-steps">
                                <li>Open your <?= e($method['label']) ?> wallet and choose <strong>Send</strong>.</li>
                                <li>Paste the address above and enter <?= $coinAmount !== '' ? 'exactly ' . e($coinAmount) : 'the amount confirmed by support' ?>.</li>
                                <li>Send the transaction and copy the transaction hash from your wallet.</li>
                                <li>Confirm below &ndash; the order moves to approved and processing straight away.</li>
                            </ol>

                            <?php if ($payable): ?>
                                <form class="ckp-form" id="payment-form" method="post"
                                      action="<?= e(url('checkout/payment/confirm')) ?>" data-payment-form>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="orderId" value="<?= e($order['order_number']) ?>">
                                    <input type="hidden" name="guest_token" value="<?= e($guestToken) ?>">

                                    <div class="ckp-field">
                                        <label for="txHash">Transaction hash <span>(optional)</span></label>
                                        <div class="ckp-input-row">
                                            <input class="ckp-input ckp-input--mono" id="txHash" type="text" name="txHash"
                                                   placeholder="<?= e($method['label']) ?> transaction hash"
                                                   maxlength="128" autocomplete="off" spellcheck="false"
                                                   aria-describedby="txHash-hint">
                                            <button type="button" class="ckp-btn" data-paste-into="#txHash">Paste</button>
                                        </div>
                                        <p class="ckp-hint" id="txHash-hint">Speeds up on-chain verification. Paste the hash from your wallet.</p>
                                    </div>

                                    <div class="ckp-field">
                                        <label for="reference">Reference <span>(optional)</span></label>
                                        <input class="ckp-input" id="reference" type="text" name="reference"
                                               placeholder="Order number or note" maxlength="190" autocomplete="off"
                                               aria-describedby="reference-hint">
                                        <p class="ckp-hint" id="reference-hint">Anything that helps support match your transfer.</p>
                                    </div>

                                    <button class="ckp-btn ckp-btn--primary ckp-btn--block ckp-form__submit" type="submit" data-submit>
                                        <span class="ckp-btn__spinner" aria-hidden="true"></span>
                                        <span data-submit-label>I have transferred the amount</span>
                                    </button>

                                    <p class="ckp-hint ckp-hint--center">
                                        Confirming approves the order for processing. We still check the transfer on-chain.
                                    </p>
                                </form>

                                <p class="ckp-expired" data-expired-note hidden>
                                    The payment window has closed. <a href="<?= e(url('all')) ?>">Start a new order</a>
                                    or contact support with your order number.
                                </p>

                                <div class="ckp-mobilebar">
                                    <span class="ckp-mobilebar__total">
                                        Amount due
                                        <strong><?= $coinAmount !== '' ? e($coinAmount) : e(money($order['total'])) ?></strong>
                                    </span>
                                    <button class="ckp-btn ckp-btn--primary" type="submit" form="payment-form" data-mobile-submit>
                                        Confirm payment
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>

                    <footer class="ckp-trust">
                        <span>
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z"></path>
                                <path d="m8.5 12 2.5 2.5 4.5-5"></path>
                            </svg>
                            Verified on-chain
                        </span>
                        <a href="<?= e(url('contact')) ?>">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M9.5 9a2.5 2.5 0 1 1 4.1 1.9c-.9.7-1.6 1.2-1.6 2.1"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                            Need help? Contact support
                        </a>
                        <span>
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="4" y="10" width="16" height="10" rx="2.5"></rect>
                                <path d="M8 10V7.5a4 4 0 0 1 8 0V10"></path>
                            </svg>
                            Encrypted checkout
                        </span>
                    </footer>
                <?php endif; ?>
            </div>

            <aside class="ckp-side" aria-label="Order summary">
                <details class="ckp-summary-wrap" data-summary open>
                    <summary>
                        <span>Order summary &middot; <?= e(money($order['total'])) ?></span>
                        <svg class="icon ckp-chevron" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </summary>

                    <div class="ckp-summary" data-summary-panel>
                        <div class="ckp-summary__head">
                            <div>
                                <span class="ckp-summary__label">Order</span>
                                <strong class="ckp-summary__id" id="ckp-order-number"><?= e($order['order_number']) ?></strong>
                                <p class="ckp-summary__meta">
                                    Placed <?= e(gmdate('M j, Y \a\t H:i', (int) $placedTs)) ?> UTC
                                </p>
                            </div>
                            <span class="ckp-pill ckp-pill--<?= e($statusTone) ?>"><?= e($statusLabel) ?></span>
                        </div>

                        <div class="ckp-summary__total">
                            <span>Total due</span>
                            <strong><?= e(money($order['total'])) ?></strong>
                        </div>

                        <div class="ckp-summary__items">
                            <?php foreach ($items as $line): ?>
                                <div class="ckp-summary__row">
                                    <span><?= e($line['name']) ?> &times; <?= (int) $line['qty'] ?></span>
                                    <span><?= e(money($line['total'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="ckp-summary__row ckp-summary__row--muted">
                                <span>Subtotal</span>
                                <span><?= e(money($order['subtotal'])) ?></span>
                            </div>
                            <?php if ((float) $order['discount'] > 0): ?>
                                <div class="ckp-summary__row ckp-summary__row--muted">
                                    <span>Discount</span>
                                    <span>-<?= e(money($order['discount'])) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="ckp-summary__row ckp-summary__row--muted">
                                <span>Shipping</span>
                                <span><?= ((float) $order['shipping_total'] > 0) ? e(money($order['shipping_total'])) : 'Free' ?></span>
                            </div>
                        </div>

                        <div class="ckp-summary__actions">
                            <button type="button" class="ckp-btn ckp-btn--quiet" data-copy="#ckp-order-number">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                    <path d="M5 15V6a2 2 0 0 1 2-2h9"></path>
                                </svg>
                                Copy order ID
                            </button>
                        </div>

                        <p class="ckp-summary__note">
                            <?= e($method['label']) ?> payments are checked on-chain.
                            Keep your transaction hash until the order is marked paid.
                        </p>
                    </div>
                </details>
            </aside>
        </div>
    </div>
</div>

<script src="<?= e(asset('js/payment-qr.js')) ?>"></script>
<script src="<?= e(asset('js/payment.js')) ?>"></script>
