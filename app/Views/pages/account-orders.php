<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><a href="<?= e(url('account')) ?>"><span>My Account</span></a></li>
                        <li><span>Orders</span></li>
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
                <h1 class="category-title">My Orders</h1>
            </div>
        </div>
    </div>
</div>

<div class="box-data">
    <div class="container">
        <div class="account-layout">
            <nav class="account-nav">
                <a href="<?= e(url('account')) ?>">Profile</a>
                <a class="active" href="<?= e(url('account/orders')) ?>">Orders</a>
                <a href="<?= e(url('logout')) ?>">Sign out</a>
            </nav>

            <div class="account-main">
                <div class="account-panel">
                    <p class="muted small mt-0">Every order on your account, newest first.</p>
                    <?php if ($orders === []): ?>
                        <p class="muted">No orders yet. <a href="<?= e(url('all')) ?>">Browse the catalogue</a> to place your first order.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong><?= e($order['order_number']) ?></strong></td>
                                            <td><?= e(date('M j, Y', strtotime($order['created_at']))) ?></td>
                                            <td><?= (int) \App\Support\Database::value('SELECT COALESCE(SUM(qty), 0) FROM order_items WHERE order_id = ?', [$order['id']]) ?></td>
                                            <td><?= money($order['total']) ?></td>
                                            <td><span class="badge badge-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                                            <td><a href="<?= e(url('account/order', ['number' => $order['order_number']])) ?>">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
