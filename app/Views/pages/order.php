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
            Complete the payment below and keep your transaction ID.
        </div>
    <?php endif; ?>

    <div class="order-card">
        <div class="d-flex justify-content-between flex-wrap" style="gap:12px;">
            <div>
                <div class="muted small">Placed <?= e(date('M j, Y \a\t H:i', strtotime($order['created_at']))) ?></div>
            </div>
            <div class="text-right">
                <span class="badge badge-<?= e($order['status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
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
        $method = \App\Support\PaymentMethod::get((string) ($order['payment_method'] ?? \App\Support\PaymentMethod::BITCOIN));
        $coinAddress = trim((string) ($order['payment_address'] ?? ''));
        if ($coinAddress === '') {
            $coinAddress = trim((string) $method['address']);
        }
        $coinAmount = \App\Support\PaymentMethod::amount($order, $method);
        $coinUri = \App\Support\PaymentMethod::uri($method, $coinAddress, $coinAmount, 'Order ' . $order['order_number']);
        $awaitingPayment = in_array((string) $order['status'], ['pending', 'pending_payment'], true);
        $coinLabel = $coinAmount !== null ? $coinAmount . ' ' . $method['symbol'] : money($order['total']);
        ?>
        <?php if ($awaitingPayment): ?>
            <section class="pay-panel" aria-labelledby="order-pay-title">
                <div class="pay-panel__status">
                    <span class="pay-panel__coin" aria-hidden="true"><?= \App\Support\PaymentMethod::icon($method['key']) ?></span>
                    <div>
                        <h2 id="order-pay-title">Pay with <?= e($method['label']) ?></h2>
                        <p>
                            Send <?= e($coinLabel) ?> to the address below. The order is confirmed after
                            <?= (int) $method['confirmations'] ?> network confirmation<?= (int) $method['confirmations'] === 1 ? '' : 's' ?>.
                        </p>
                    </div>
                </div>

                <?php if ($coinAddress === ''): ?>
                    <p class="pay-note">
                        <?= e($method['label']) ?> payment details are not configured yet. Please contact support and quote order
                        <strong><?= e($order['order_number']) ?></strong> before sending any payment.
                    </p>
                <?php else: ?>
                    <div class="pay-amount">
                        <div class="pay-amount__main">
                            <span class="pay-amount__label">Amount to send</span>
                            <span class="pay-amount__value"><?= e($coinLabel) ?></span>
                            <span class="pay-amount__fiat">
                                <?= $coinAmount !== null
                                    ? 'equals ' . e(money($order['total']))
                                    : 'The ' . e($method['symbol']) . ' equivalent is confirmed by support before you send.' ?>
                            </span>
                        </div>
                        <div class="pay-amount__network">
                            <span>Network</span>
                            <strong><?= e($method['network']) ?></strong>
                        </div>
                    </div>

                    <div class="pay-address">
                        <span class="pay-address__label"><?= e($method['label']) ?> address</span>
                        <div class="pay-address__row">
                            <code id="order-pay-address"><?= e($coinAddress) ?></code>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-copy-target="order-pay-address">Copy</button>
                        </div>
                    </div>

                    <div class="pay-panel__actions">
                        <?php if ($coinUri !== ''): ?>
                            <a class="btn btn-mix btn-primary" href="<?= e($coinUri) ?>">Open in <?= e($method['label']) ?> wallet</a>
                        <?php endif; ?>
                        <a class="btn btn-mix btn-primary" href="<?= e(url('checkout/payment', ['orderId' => $order['order_number']])) ?>">Continue to payment</a>
                        <a class="btn btn-mix btn-outline-primary" href="<?= e(url('contact')) ?>">Need help?</a>
                    </div>

                    <ol class="pay-steps">
                        <li>Open your <?= e($method['label']) ?> wallet and choose <strong>Send</strong>.</li>
                        <li>Paste the address above and enter <?= $coinAmount !== null ? 'exactly ' . e($coinLabel) : 'the amount confirmed by support' ?>.</li>
                        <li>Send the transaction and keep the transaction ID.</li>
                        <li>We confirm the order after <?= (int) $method['confirmations'] ?> network confirmation<?= (int) $method['confirmations'] === 1 ? '' : 's' ?>.</li>
                    </ol>

                    <p class="pay-warning">
                        Send only <?= e($method['label']) ?> (<?= e($method['symbol']) ?>) on the <?= e($method['network']) ?> network to this address.
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
