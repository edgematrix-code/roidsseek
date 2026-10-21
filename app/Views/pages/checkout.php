<?php $currentUser = user(); ?>
<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><a href="<?= e(url('cart')) ?>"><span>Shopping Cart</span></a></li>
                        <li><span>Checkout</span></li>
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
                <h1 class="category-title">Checkout</h1>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php if ($errors !== []): ?>
        <div class="site-flash site-flash--error">Please correct the highlighted fields and try again.</div>
    <?php endif; ?>

    <form action="<?= e(url('checkout')) ?>" method="post" class="mt-2">
        <?= csrf_field() ?>
        <div class="checkout-layout">
            <div class="checkout-main">
                <div class="checkout-panel">
                    <h2>Contact</h2>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="email" class="required">Email address</label>
                            <input class="form-control" id="email" type="email" name="email" required
                                   value="<?= e(old('email', $currentUser['email'] ?? '')) ?>">
                            <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
                            <div class="form-hint">Your order confirmation and tracking updates go here.</div>
                        </div>
                    </div>
                </div>

                <div class="checkout-panel">
                    <h2>Shipping address</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="shipping_name" class="required">Full name</label>
                            <input class="form-control" id="shipping_name" type="text" name="shipping_name" required
                                   value="<?= e(old('shipping_name', $currentUser['name'] ?? '')) ?>">
                            <?php if (isset($errors['shipping_name'])): ?><div class="field-error"><?= e($errors['shipping_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="shipping_phone">Phone</label>
                            <input class="form-control" id="shipping_phone" type="text" name="shipping_phone"
                                   value="<?= e(old('shipping_phone', $currentUser['phone'] ?? '')) ?>">
                        </div>
                        <div class="form-group full">
                            <label for="shipping_address" class="required">Address</label>
                            <input class="form-control" id="shipping_address" type="text" name="shipping_address" required
                                   value="<?= e(old('shipping_address')) ?>">
                            <?php if (isset($errors['shipping_address'])): ?><div class="field-error"><?= e($errors['shipping_address']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="shipping_city">City</label>
                            <input class="form-control" id="shipping_city" type="text" name="shipping_city" value="<?= e(old('shipping_city')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="shipping_state">State / region</label>
                            <input class="form-control" id="shipping_state" type="text" name="shipping_state" value="<?= e(old('shipping_state')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="shipping_postal">Postal code</label>
                            <input class="form-control" id="shipping_postal" type="text" name="shipping_postal" value="<?= e(old('shipping_postal')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="shipping_country" class="required">Country</label>
                            <input class="form-control" id="shipping_country" type="text" name="shipping_country" required value="<?= e(old('shipping_country', 'United States')) ?>">
                            <?php if (isset($errors['shipping_country'])): ?><div class="field-error"><?= e($errors['shipping_country']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group full">
                            <label for="notes">Order notes (optional)</label>
                            <textarea class="form-control" id="notes" name="notes" placeholder="Delivery instructions, cycle notes, etc."><?= e(old('notes')) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="checkout-panel checkout-panel--btc">
                    <h2>Payment method</h2>
                    <input type="hidden" name="payment_method" value="bitcoin">
                    <div class="payment-option payment-option--selected">
                        <span class="btc-mark" aria-hidden="true">&#8383;</span>
                        <span>
                            <strong>Bitcoin (BTC) only</strong>
                            <small>The exact amount and receive address are shown on the confirmation page after you place the order. Send only Bitcoin to that address.</small>
                        </span>
                        <span class="payment-check" aria-hidden="true">&#10003;</span>
                    </div>
                    <?php if (isset($errors['payment_method'])): ?><div class="field-error"><?= e($errors['payment_method']) ?></div><?php endif; ?>
                    <p class="small muted mt-2">
                        Orders are marked paid after the configured number of network confirmations.
                        Keep your transaction ID for support.
                    </p>
                </div>
            </div>

            <aside class="checkout-aside">
                <div class="summary">
                    <h3>Your order</h3>
                    <?php foreach ($items as $row): ?>
                        <div class="checkout-summary-item">
                            <span><?= e($row['product']['name']) ?> &times; <?= (int) $row['qty'] ?></span>
                            <span><?= money($row['line_total']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-row mt-2"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
                    <?php if ($discount > 0): ?>
                        <div class="summary-row"><span>Discount</span><span>-<?= money($discount) ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? money($shipping) : 'Free' ?></span></div>
                    <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>
                    <button type="submit" class="btn btn-mix btn-primary btn-block">Place order</button>
                    <p class="small muted mt-2">By placing the order you agree to our terms of use.</p>
                </div>
            </aside>
        </div>
    </form>
</div>
