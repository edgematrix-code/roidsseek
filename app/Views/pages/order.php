<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><a href="<?= e(url('account/orders')) ?>"><span>Orders</span></a></li>
                        <li><span><?= e($order['order_number']) ?></span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-cat-select">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="category-title">Order <?= e($order['order_number']) ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="container order-view">
    <?php if ($justPlaced): ?>
        <div class="site-flash site-flash--success">
            Thank you! Your order <strong><?= e($order['order_number']) ?></strong> has been received.
            Send the Bitcoin payment below and keep your transaction ID.
        </div>
    <?php endif; ?>

    <div class="order-card">
        <div class="d-flex justify-content-between flex-wrap" style="gap:12px;">
            <div>
                <div class="muted small">Placed <?= e(date('M j, Y \a\t H:i', strtotime($order['created_at']))) ?></div>
            </div>
            <div class="text-right">
                <span class="badge badge-<?= e($order['status']) ?>"><?= e($order['status']) ?></span>
                <div class="small muted mt-2">Payment: <?= e(str_replace('_', ' ', $order['payment_method'])) ?></div>
            </div>
        </div>

        <div class="order-grid">
            <div>
                <h4>Shipping address</h4>
                <div><?= e($order['shipping_name']) ?></div>
                <div><?= e($order['shipping_address']) ?></div>
                <div><?= e(trim($order['shipping_city'] . ' ' . $order['shipping_state'] . ' ' . $order['shipping_postal'])) ?></div>
                <div><?= e($order['shipping_country']) ?></div>
                <?php if ($order['shipping_phone']): ?><div class="small muted"><?= e($order['shipping_phone']) ?></div><?php endif; ?>
            </div>
            <div>
                <h4>Contact</h4>
                <div><?= e($order['email']) ?></div>
                <?php if ($order['notes']): ?>
                    <h4 class="mt-2">Notes</h4>
                    <div class="small"><?= nl2br(e($order['notes'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td class="small muted"><?= e($item['sku']) ?></td>
                            <td><?= money($item['unit_price']) ?></td>
                            <td><?= (int) $item['qty'] ?></td>
                            <td class="text-right"><?= money($item['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="summary mt-2" style="max-width:360px;margin-left:auto;">
            <div class="summary-row"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
            <?php if ((float) $order['discount'] > 0): ?>
                <div class="summary-row"><span>Discount</span><span>-<?= money($order['discount']) ?></span></div>
            <?php endif; ?>
            <div class="summary-row"><span>Shipping</span><span><?= ((float) $order['shipping_total'] > 0) ? money($order['shipping_total']) : 'Free' ?></span></div>
            <div class="summary-row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
        </div>

        <?php
        $btc = (array) config('payments.btc', []);
        $btcAddress = trim((string) ($btc['address'] ?? ''));
        $btcRate = (float) ($btc['usd_rate'] ?? 0);
        $btcAmount = $btcRate > 0 ? (float) $order['total'] / $btcRate : null;
        $btcLabel = trim((string) ($btc['label'] ?? '')) !== '' ? (string) $btc['label'] : 'Order ' . $order['order_number'];
        $bip21 = 'bitcoin:' . $btcAddress . '?label=' . rawurlencode($btcLabel);
        if ($btcAmount !== null) {
            $bip21 .= '&amount=' . number_format($btcAmount, 8, '.', '');
        }
        $awaitingPayment = $order['payment_method'] === 'bitcoin' && $order['status'] === 'pending';
        ?>
        <?php if ($awaitingPayment): ?>
            <section class="btc-panel" aria-labelledby="btc-panel-title">
                <div class="btc-panel__head">
                    <span class="btc-mark btc-mark--lg" aria-hidden="true">&#8383;</span>
                    <div>
                        <h2 id="btc-panel-title">Pay with Bitcoin</h2>
                        <p>Send the amount below to the address shown. The order is confirmed after
                            <?= (int) ($btc['confirmations'] ?? 2) ?> network confirmation<?= (int) ($btc['confirmations'] ?? 2) === 1 ? '' : 's' ?>.</p>
                    </div>
                </div>

                <?php if ($btcAddress === ''): ?>
                    <p class="btc-panel__note">
                        Bitcoin payment details are not configured yet. Please contact support and quote order
                        <strong><?= e($order['order_number']) ?></strong> before sending any payment.
                    </p>
                <?php else: ?>
                    <?php if ($btcAmount !== null): ?>
                        <div class="btc-amount">
                            <span class="btc-amount__value"><?= e(number_format($btcAmount, 8, '.', '')) ?> BTC</span>
                            <span class="btc-amount__fiat"><?= e(money($order['total'])) ?> at <?= e(number_format($btcRate, 2, '.', ',')) ?> <?= e(\App\Support\Site::currency() ?: 'USD') ?>/BTC</span>
                        </div>
                    <?php else: ?>
                        <div class="btc-amount">
                            <span class="btc-amount__value"><?= e(money($order['total'])) ?></span>
                            <span class="btc-amount__fiat">The BTC equivalent is confirmed by support before you send.</span>
                        </div>
                    <?php endif; ?>

                    <div class="btc-address">
                        <span class="btc-address__label">Bitcoin address</span>
                        <div class="btc-address__row">
                            <code id="btc-address"><?= e($btcAddress) ?></code>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-copy-target="btc-address">Copy</button>
                        </div>
                    </div>

                    <div class="btc-panel__actions">
                        <a class="btn btn-mix btn-primary" href="<?= e($bip21) ?>">Open in Bitcoin wallet</a>
                        <a class="btn btn-mix btn-outline-primary" href="<?= e(url('contact')) ?>">Need help?</a>
                    </div>

                    <ol class="btc-steps">
                        <li>Open your Bitcoin wallet and choose <strong>Send</strong>.</li>
                        <li>Paste the address above and enter the exact BTC amount.</li>
                        <li>Send the transaction and keep the transaction ID.</li>
                        <li>We mark the order paid after <?= (int) ($btc['confirmations'] ?? 2) ?> confirmation<?= (int) ($btc['confirmations'] ?? 2) === 1 ? '' : 's' ?>.</li>
                    </ol>

                    <p class="btc-warning">
                        Send only Bitcoin (BTC) on the Bitcoin network to this address.
                        Payments in any other asset or network cannot be recovered.
                    </p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div class="buy-row mt-2">
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Continue shopping</a>
            <?php if (is_logged_in()): ?>
                <a class="btn btn-mix btn-primary" href="<?= e(url('account/orders')) ?>">My orders</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var button = document.querySelector('[data-copy-target]');
    if (!button) { return; }
    button.addEventListener('click', function () {
        var target = document.getElementById(button.getAttribute('data-copy-target'));
        if (!target) { return; }
        var text = target.textContent.trim();
        var done = function () {
            var original = button.textContent;
            button.textContent = 'Copied';
            setTimeout(function () { button.textContent = original; }, 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, done);
            return;
        }
        var range = document.createRange();
        range.selectNodeContents(target);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        try { document.execCommand('copy'); } catch (error) {}
        done();
    });
})();
</script>
