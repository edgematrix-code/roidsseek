<div class="admin-topbar">
    <h1>Orders</h1>
    <form class="admin-search" method="get" action="<?= e(url()) ?>">
        <input type="hidden" name="<?= e(route_param()) ?>" value="admin/orders">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['pending_payment', 'processing', 'payment_submitted', 'paid', 'failed', 'expired', 'pending', 'shipped', 'completed', 'cancelled'] as $option): ?>
                <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $option)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-light" type="submit">Filter</button>
    </form>
</div>

<div class="admin-panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Order</th><th>Customer</th><th>Email</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?= e($order['order_number']) ?></strong></td>
                        <td><?= e($order['shipping_name']) ?></td>
                        <td class="small"><?= e($order['email']) ?></td>
                        <td><?= money($order['total']) ?></td>
                        <td><span class="badge badge-<?= e($order['status']) ?>"><?= ucfirst($order['status']) ?></span></td>
                        <td><?= str_replace('_', ' ', $order['payment_method']) ?></td>
                        <td class="small"><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></td>
                        <td><a href="<?= e(url('admin/order', ['id' => $order['id']])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($orders === []): ?>
                    <tr><td colspan="7" class="muted">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager['pages'] > 1): ?>
        <nav class="pagination">
            <?php for ($page = 1; $page <= $pager['pages']; $page++): ?>
                <?php if ($page === $pager['current']): ?>
                    <span class="current"><?= $page ?></span>
                <?php else: ?>
                    <a href="<?= e(url('admin/orders', ['status' => $status, 'page' => $page])) ?>"><?= $page ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>
