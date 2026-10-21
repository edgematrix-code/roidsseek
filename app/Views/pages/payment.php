<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><a href="<?= e(url('cart')) ?>"><span>Shopping Cart</span></a></li>
                        <li><a href="<?= e(url('checkout')) ?>"><span>Checkout</span></a></li>
                        <li><span>Pay with Bitcoin</span></li>
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
                <h1 class="category-title">Pay with Bitcoin</h1>
            </div>
        </div>
    </div>
</div>

<div class="container payment-page">
    <div class="payment-card">
        <div class="payment-card__top">
            <div>
                <div class="payment-card__eyebrow small muted">Order <?= e($order['order_number']) ?></div>
                <h2>Send Bitcoin to complete your order</h2>
                <p class="small muted">
                    Your order is reserved for <?= e($btcConfirmations) ?> network confirmation<?= $btcConfirmations === 1 ? '' : 's' ?> after we detect the payment.
                    Send only Bitcoin (BTC) on the Bitcoin network to the address below.
                </p>
            </div>
            <div class="payment-card__amount">
                <?php if ($btcAmount !== null): ?>
                    <div class="payment-amount">
                        <div class="payment-amount__btc"><?= e(number_format($btcAmount, 8, '.', '')) ?> BTC</div>
                        <div class="payment-amount__fiat"><?= e(money($order['total'])) ?> at <?= e(number_format($btcRate, 2, '.', ',')) ?> USD/BTC</div>
                    </div>
                <?php else: ?>
                    <div class="payment-amount">
                        <div class="payment-amount__btc">Contact support for the BTC amount</div>
                        <div class="payment-amount__fiat muted">USD rate not configured yet</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="btc-address-card">
            <div class="btc-address-card__row">
                <span class="btc-mark btc-mark--lg" aria-hidden="true">&#8383;</span>
                <div>
                    <div class="btc-address-card__label">Receive address</div>
                    <div>
                        <?php if ($btcAddress !== ''): ?>
                            <div class="btc-address-block">
                                <code id="btc-address"><?= e($btcAddress) ?></code>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-target="btc-address">Copy address</button>
                            </div>
                            <div class="btc-actions mt-2">
                                <a class="btn btn-mix btn-primary" href="<?= e($btcBip21) ?>" target="_blank" rel="noopener">Open in wallet</a>
                                <a class="btn btn-mix btn-outline-primary" href="<?= e(url('contact')) ?>">Need help?</a>
                            </div>
                        <?php else: ?>
                            <div class="btc-placeholder">
                                Bitcoin payment details are not configured yet. Please contact support and quote order <strong><?= e($order['order_number']) ?></strong> before sending any payment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <ol class="btc-steps mt-2">
                <li>Open your Bitcoin wallet and choose <strong>Send</strong>.</li>
                <li>Paste the address above and enter the exact BTC amount shown.</li>
                <li>Send the transaction and keep the transaction ID for your records.</li>
                <li>Come back to this page and tap <strong>I have transferred</strong> once the payment is sent.</li>
            </ol>

            <p class="btc-warning mt-2">
                This address accepts Bitcoin only. Do not send any other asset or use any other network — those payments cannot be recovered.
            </p>

            <form method="post" action="<?= e(url('payment/confirm')) ?>" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="number" value="<?= e($order['order_number']) ?>">
                <button type="submit" class="btn btn-mix btn-primary btn-lg btn-block">I have transferred the amount</button>
            </form>
        </div>

        <div class="payment-summary mt-2">
            <div class="summary">
                <h3>Order summary</h3>
                <?php foreach ($items as $item): ?>
                    <div class="checkout-summary-item">
                        <span><?= e($item['name']) ?> &times; <?= (int) $item['qty'] ?></span>
                        <span><?= money($item['total']) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-row mt-2"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
                <?php if ((float) $order['discount'] > 0): ?>
                    <div class="summary-row"><span>Discount</span><span>-<?= money($order['discount']) ?></span></div>
                <?php endif; ?>
                <div class="summary-row"><span>Shipping</span><span><?= ((float) $order['shipping_total'] > 0) ? money($order['shipping_total']) : 'Free' ?></span></div>
                <div class="summary-row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
            </div>
        </div>

        <div class="buy-row mt-2">
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('order', ['number' => $order['order_number']])) ?>">View order</a>
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Continue shopping</a>
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