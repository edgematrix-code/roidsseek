<div class="admin-topbar">
    <h1>Order <?= e($order['order_number']) ?></h1>
    <a class="btn btn-light" href="<?= e(url('admin/orders')) ?>">&larr; All orders</a>
</div>

<div class="admin-cards">
    <div class="stat-card"><span>Total</span><strong><?= money($order['total']) ?></strong></div>
    <div class="stat-card"><span>Status</span><strong style="font-size:18px;"><?= e(ucfirst($order['status'])) ?></strong></div>
    <div class="stat-card"><span>Payment</span><strong style="font-size:18px;"><?= e(str_replace('_', ' ', $order['payment_method'])) ?></strong></div>
    <div class="stat-card"><span>Placed</span><strong style="font-size:16px;"><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></strong></div>
</div>

<div class="admin-panel">
    <h2>Update status</h2>
    <form method="post" action="<?= e(url('admin/order/status')) ?>" style="display:flex;gap:10px;align-items:center;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
        <select name="status">
            <?php foreach (['pending', 'paid', 'shipped', 'completed', 'cancelled'] as $option): ?>
                <option value="<?= $option ?>" <?= $order['status'] === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</div>

<div class="admin-panel">
    <h2>Items</h2>
    <table class="data-table">
        <thead><tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th class="text-right">Total</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td class="small"><?= e($item['sku']) ?></td>
                    <td><?= money($item['unit_price']) ?></td>
                    <td><?= (int) $item['qty'] ?></td>
                    <td class="text-right"><?= money($item['total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="summary mt-2" style="max-width:340px;margin-left:auto;">
        <div class="summary-row"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
        <div class="summary-row"><span>Discount</span><span>-<?= money($order['discount']) ?></span></div>
        <div class="summary-row"><span>Shipping</span><span><?= money($order['shipping_total']) ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
    </div>
</div>

<div class="admin-panel">
    <h2>Customer &amp; shipping</h2>
    <div class="form-grid">
        <div>
            <h4 class="muted small">Customer</h4>
            <p>
                <?= e($order['shipping_name']) ?><br>
                <?= e($order['email']) ?><br>
                <?= e($order['shipping_phone']) ?>
                <?php if ($customer): ?>
                    <br><a class="small" href="<?= e(url('admin/users')) ?>">Account #<?= (int) $customer['id'] ?></a>
                <?php endif; ?>
            </p>
        </div>
        <div>
            <h4 class="muted small">Ship to</h4>
            <p>
                <?= e($order['shipping_address']) ?><br>
                <?= e(trim($order['shipping_city'] . ' ' . $order['shipping_state'] . ' ' . $order['shipping_postal'])) ?><br>
                <?= e($order['shipping_country']) ?>
            </p>
        </div>
    </div>
    <?php if ($order['notes']): ?>
        <h4 class="muted small">Notes</h4>
        <p><?= nl2br(e($order['notes'])) ?></p>
    <?php endif; ?>
</div>
