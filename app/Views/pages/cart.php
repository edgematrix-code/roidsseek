<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Shopping Cart</span></li>
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
                <h1 class="category-title">Shopping Cart</h1>
            </div>
        </div>
    </div>
</div>

<div class="box-data cart-page">
    <div class="container">
        <?php if ($items === []): ?>
            <div class="empty-state">
                <h2 class="h1"><span class="no-items">Shopping cart is empty</span></h2>
                <p>Browse the catalogue and add a few products to get started.</p>
                <a class="btn btn-mix btn-primary" href="<?= e(url('all')) ?>">Start shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-main">
                    <form action="<?= e(url('cart/update')) ?>" method="post">
                        <?= csrf_field() ?>
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $row): $product = $row['product']; ?>
                                    <tr>
                                        <td>
                                            <div class="cart-product">
                                                <img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                                                <div>
                                                    <a class="name" href="<?= e(url('product', ['slug' => $product['slug']])) ?>"><?= e($product['name']) ?></a>
                                                    <div class="meta"><?= e($product['sku']) ?><?= $product['pack'] ? ' &middot; ' . e($product['pack']) : '' ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= money($row['unit_price']) ?></td>
                                        <td>
                                            <div class="cart-qty">
                                                <input type="number" name="qty[<?= (int) $product['id'] ?>]" value="<?= (int) $row['qty'] ?>" min="0" max="<?= max(1, (int) $product['stock']) ?>">
                                            </div>
                                        </td>
                                        <td><strong><?= money($row['line_total']) ?></strong></td>
                                        <td>
                                            <button type="submit" form="remove-<?= (int) $product['id'] ?>" class="btn btn-sm btn-outline-primary">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="cart-actions">
                            <button type="submit" class="btn btn-mix btn-primary">Update cart</button>
                            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Continue shopping</a>
                        </div>
                    </form>

                    <?php foreach ($items as $row): $product = $row['product']; ?>
                        <form id="remove-<?= (int) $product['id'] ?>" action="<?= e(url('cart/remove')) ?>" method="post" style="display:none;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        </form>
                    <?php endforeach; ?>
                </div>

                <aside class="cart-aside">
                    <div class="summary">
                        <h3>Order summary</h3>
                        <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
                        <?php if ($discount > 0): ?>
                            <div class="summary-row"><span>Discount (<?= e($coupon['code']) ?>)</span><span>-<?= money($discount) ?></span></div>
                        <?php endif; ?>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?= $shipping > 0 ? money($shipping) : 'Free' ?></span>
                        </div>
                        <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>

                        <?php if ($coupon): ?>
                            <form action="<?= e(url('cart/coupon')) ?>" method="post" class="coupon-form">
                                <?= csrf_field() ?>
                                <input type="text" value="<?= e($coupon['code']) ?>" disabled>
                                <button class="btn btn-sm btn-outline-primary" type="submit" name="remove" value="1">Remove</button>
                            </form>
                        <?php else: ?>
                            <form action="<?= e(url('cart/coupon')) ?>" method="post" class="coupon-form">
                                <?= csrf_field() ?>
                                <input type="text" name="code" placeholder="Coupon code">
                                <button class="btn btn-sm btn-outline-primary" type="submit">Apply</button>
                            </form>
                        <?php endif; ?>

                        <a class="btn btn-mix btn-primary btn-block" href="<?= e(url('checkout')) ?>">Checkout</a>
                        <p class="small muted mt-2">Free shipping on orders over <?= money(config('app.free_shipping_threshold')) ?>.</p>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</div>
