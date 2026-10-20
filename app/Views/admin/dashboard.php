<div class="admin-topbar">
    <h1>Dashboard</h1>
    <div class="admin-search">
        <a class="btn btn-primary" href="<?= e(url('admin/product')) ?>">+ New product</a>
    </div>
</div>

<div class="admin-cards">
    <div class="stat-card"><span>Products</span><strong><?= number_format($stats['products']) ?></strong></div>
    <div class="stat-card"><span>Orders</span><strong><?= number_format($stats['orders']) ?></strong></div>
    <div class="stat-card"><span>Pending orders</span><strong><?= number_format($stats['pending_orders']) ?></strong></div>
    <div class="stat-card"><span>Revenue</span><strong><?= money($stats['revenue']) ?></strong></div>
    <div class="stat-card"><span>Customers</span><strong><?= number_format($stats['customers']) ?></strong></div>
    <div class="stat-card"><span>Pending reviews</span><strong><?= number_format($stats['reviews']) ?></strong></div>
    <div class="stat-card"><span>Unread messages</span><strong><?= number_format($stats['messages']) ?></strong></div>
    <div class="stat-card"><span>Subscribers</span><strong><?= number_format($stats['subscribers']) ?></strong></div>
</div>

<div class="admin-panel">
    <h2>Recent orders</h2>
    <?php if ($recentOrders === []): ?>
        <p class="muted">No orders have been placed yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><a href="<?= e(url('admin/order', ['id' => $order['id']])) ?>"><?= e($order['order_number']) ?></a></td>
                            <td><?= e($order['shipping_name']) ?><div class="small muted"><?= e($order['email']) ?></div></td>
                            <td><?= money($order['total']) ?></td>
                            <td><span class="badge badge-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                            <td><?= e(date('M j, H:i', strtotime($order['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin-panel">
    <h2>Best sellers</h2>
    <?php if ($topProducts === []): ?>
        <p class="muted">Sales data will appear here once orders start coming in.</p>
    <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Product</th><th>Units sold</th><th class="text-right">Revenue</th></tr></thead>
            <tbody>
                <?php foreach ($topProducts as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= (int) $row['sold'] ?></td>
                        <td class="text-right"><?= money($row['revenue']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
