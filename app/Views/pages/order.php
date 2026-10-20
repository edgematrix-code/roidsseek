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
            We will email payment instructions to <?= e($order['email']) ?>.
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

        <div class="buy-row mt-2">
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Continue shopping</a>
            <?php if (is_logged_in()): ?>
                <a class="btn btn-mix btn-primary" href="<?= e(url('account/orders')) ?>">My orders</a>
            <?php endif; ?>
        </div>
    </div>
</div>
