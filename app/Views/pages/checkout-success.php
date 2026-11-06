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
                <h1 class="category-title">Payment received</h1>
            </div>
        </div>
    </div>
</div>

<div class="container order-view">
    <div class="site-flash site-flash--success">
        Thank you! Your payment for order <strong><?= e($order['order_number']) ?></strong> has been received
        and the order is confirmed.
    </div>

    <div class="order-card">
        <div class="d-flex justify-content-between flex-wrap" style="gap:12px;">
            <div>
                <div class="muted small">Order <?= e($order['order_number']) ?></div>
                <div class="muted small">Placed <?= e(date('M j, Y \a\t H:i', strtotime($order['created_at']))) ?></div>
            </div>
            <div class="text-right">
                <span class="badge badge-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
                <div class="small muted mt-2">Payment: <?= e(str_replace('_', ' ', $order['payment_method'])) ?></div>
            </div>
        </div>

        <div class="order-grid">
            <div>
                <h4>Transaction</h4>
                <div class="small">Hash: <code><?= e($order['payment_tx'] !== null && $order['payment_tx'] !== '' ? $order['payment_tx'] : 'not supplied') ?></code></div>
                <div class="small muted">Confirmed: <?= e($order['payment_verified_at'] ? date('M j, Y H:i', strtotime($order['payment_verified_at'])) . ' UTC' : 'awaiting on-chain confirmation') ?></div>
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

        <div class="buy-row mt-2">
            <a class="btn btn-mix btn-primary" href="<?= e(url('order', ['number' => $order['order_number']])) ?>">View order</a>
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Continue shopping</a>
        </div>
    </div>
</div>
