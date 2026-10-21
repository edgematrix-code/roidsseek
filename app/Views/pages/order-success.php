<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><a href="<?= e(url('cart')) ?>"><span>Shopping Cart</span></a></li>
                        <li><a href="<?= e(url('checkout')) ?>"><span>Checkout</span></a></li>
                        <li><a href="<?= e(url('payment', ['number' => $order['order_number']])) ?>"><span>Payment</span></a></li>
                        <li><span>Order paid</span></li>
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
                <h1 class="category-title">Order paid</h1>
            </div>
        </div>
    </div>
</div>

<div class="container order-success">
    <div class="success-card">
        <div class="success-card__mark" aria-hidden="true">&#10003;</div>
        <h2>Your order is confirmed</h2>
        <p class="lead">Thank you for your order <strong><?= e($order['order_number']) ?></strong>.</p>
        <div class="success-card__meta small muted">
            Payment received via Bitcoin (BTC) · Order status: <span class="badge badge-paid">Paid</span>
        </div>

        <div class="success-card__summary mt-2">
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

        <div class="success-card__actions mt-2">
            <a class="btn btn-mix btn-primary btn-lg btn-block" href="<?= e(url('account/orders')) ?>">My orders</a>
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Continue shopping</a>
        </div>
    </div>
</div>