<?php $currentUser = user(); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
/* ===== Checkout redesign (scoped to .co) ===== */
.co {
    --co-ink: #14213d;
    --co-ink-soft: #4a5568;
    --co-line: #e3e6ec;
    --co-bg: #f5f6f9;
    --co-card: #ffffff;
    --co-gold: #005db3;
    --co-gold-dark: #003567;
    --co-accent: #005db3;
    --co-err: #b3261e;
    --co-radius: 10px;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    color: var(--co-ink);
    background: var(--co-bg);
    padding-bottom: 64px;
}
.co *, .co *::before, .co *::after { box-sizing: border-box; }

/* Breadcrumb + title */
.co .breadcrumb_container { background: transparent; padding: 18px 0 0; }
.co .breadcrumb ol { display: flex; flex-wrap: wrap; gap: 8px; list-style: none; margin: 0; padding: 0; font-size: 13px; color: var(--co-ink-soft); }
.co .breadcrumb li + li::before { content: "/"; margin-right: 8px; color: #b6bcc8; }
.co .breadcrumb a { color: var(--co-ink-soft); text-decoration: none; }
.co .breadcrumb a:hover { color: var(--co-gold-dark); }
.co .box-cat-select { background: transparent; padding: 8px 0 18px; }
.co .category-title {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(34px, 5vw, 48px);
    font-weight: 700; letter-spacing: .2px; margin: 0; color: var(--co-ink);
}

/* Festive banner */
.co-banner {
    position: relative; overflow: hidden; min-height: 150px;
    display: flex; align-items: center;
    border-radius: 14px; margin: 0 0 28px;
    background: #14213d url('<?= e(url('assets/images/discount-image.jpg')) ?>') center / cover no-repeat;
    box-shadow: 0 10px 30px rgba(20, 33, 61, .18);
}
.co-banner::before {
    content: ""; position: absolute; inset: 0;
    background: linear-gradient(90deg, rgba(20,33,61,.88) 0%, rgba(20,33,61,.62) 55%, rgba(20,33,61,.15) 100%);
}
.co-banner::after {
    content: ""; position: absolute; inset: 8px; border: 1px solid rgba(160,205,255,.6); border-radius: 9px; pointer-events: none;
}
.co-banner__body { position: relative; z-index: 1; padding: 28px 36px; color: #fff; max-width: 640px; }
.co-banner__tag {
    display: inline-block; background: var(--co-gold); color: #fff;
    font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
    padding: 4px 12px; border-radius: 999px; margin-bottom: 10px;
}
.co-banner__title { font-family: 'Cormorant Garamond', Georgia, serif; font-size: clamp(24px, 3.4vw, 36px); font-weight: 700; line-height: 1.15; margin: 0 0 6px; color: #fff; }
.co-banner__text { margin: 0; font-size: 14.5px; color: #e8ecf5; line-height: 1.55; }
.co-banner__text strong { color: #a9d3ff; font-weight: 600; }

/* Layout */
.co .checkout-layout { display: grid; grid-template-columns: minmax(0, 1fr) 390px; gap: 28px; align-items: start; }
.co .checkout-main { display: grid; gap: 20px; }

/* Panels */
.co .checkout-panel {
    background: var(--co-card); border: 1px solid var(--co-line);
    border-radius: var(--co-radius); padding: 26px 28px;
    box-shadow: 0 1px 2px rgba(20,33,61,.04), 0 8px 24px rgba(20,33,61,.04);
}
.co .checkout-panel h2 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 26px; font-weight: 700; margin: 0 0 18px; padding-bottom: 12px;
    border-bottom: 1px solid var(--co-line); color: var(--co-ink);
    display: flex; align-items: center; gap: 10px;
}
.co .checkout-panel h2::before { content: ""; width: 4px; height: 22px; background: var(--co-gold); border-radius: 2px; }

/* Form */
.co .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 18px; }
.co .form-group.full { grid-column: 1 / -1; }
.co .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--co-ink); margin-bottom: 6px; }
.co .form-group label.required::after { content: " *"; color: var(--co-err); }
.co .form-control {
    width: 100%; height: 46px; padding: 0 14px;
    font: inherit; font-size: 15px; color: var(--co-ink);
    background: #fff; border: 1px solid #cfd5df; border-radius: 8px;
    transition: border-color .15s, box-shadow .15s;
}
.co textarea.form-control { height: auto; min-height: 96px; padding: 12px 14px; resize: vertical; }
.co .form-control::placeholder { color: #9aa3b2; }
.co .form-control:hover { border-color: #aab3c2; }
.co .form-control:focus { outline: none; border-color: var(--co-gold); box-shadow: 0 0 0 3px rgba(0,93,179,.18); }
.co .field-error { color: var(--co-err); font-size: 12.5px; margin-top: 5px; }
.co .form-hint { color: var(--co-ink-soft); font-size: 12.5px; margin-top: 5px; }
.co .site-flash--error {
    background: #fdf1f0; border: 1px solid #f0c4c0; border-left: 4px solid var(--co-err);
    color: #7a1a14; padding: 12px 16px; border-radius: 8px; margin: 0 0 20px; font-size: 14px;
}

/* Discount row */
.co .input-group { display: flex; gap: 10px; }
.co .input-group .form-control { flex: 1; }
.co .input-group .btn { margin: 0; height: 46px; padding: 0 24px; flex: none; }

/* Buttons (classic, solid) */
.co .btn {
    display: inline-flex; align-items: center; justify-content: center;
    font: inherit; font-size: 15px; font-weight: 600; letter-spacing: .02em;
    border: 1px solid var(--co-accent); background: var(--co-accent); color: #fff;
    border-radius: 8px; padding: 0 22px; cursor: pointer; text-decoration: none;
    transition: background .15s, border-color .15s, transform .05s;
}
.co .btn:hover { background: #003567; border-color: #003567; }
.co .btn:active { transform: translateY(1px); }
.co .btn:focus-visible { outline: 3px solid rgba(0,93,179,.45); outline-offset: 2px; }
.co .btn-block { width: 100%; height: 52px; font-size: 16px; margin-top: 18px; background: var(--co-gold); border-color: var(--co-gold); }
.co .btn-block:hover { background: var(--co-gold-dark); border-color: var(--co-gold-dark); }

/* Payment methods */
.co .pay-methods { display: grid; gap: 12px; }
.co .pay-method {
    position: relative; display: flex; align-items: center; gap: 14px;
    padding: 16px 18px; border: 1px solid #cfd5df; border-radius: 10px;
    background: #fff; cursor: pointer; transition: border-color .15s, background .15s, box-shadow .15s;
}
.co .pay-method:hover { border-color: #aab3c2; }
.co .pay-method input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.co .pay-method:focus-within { box-shadow: 0 0 0 3px rgba(0,93,179,.2); }
.co .pay-method__mark {
    flex: none; width: 44px; height: 44px; border-radius: 50%;
    display: grid; place-items: center; background: #f1f3f8; color: var(--co-ink);
}
.co .pay-method__mark svg, .co .pay-method__mark img { width: 24px; height: 24px; }
.co .pay-method__text { flex: 1; min-width: 0; display: grid; gap: 3px; }
.co .pay-method__text strong { font-size: 15px; }
.co .pay-method__text small { font-size: 12.5px; line-height: 1.5; color: var(--co-ink-soft); }
.co .pay-method__check {
    flex: none; width: 24px; height: 24px; border-radius: 50%;
    display: grid; place-items: center; font-size: 13px; color: transparent;
    border: 1.5px solid #cfd5df; background: #fff; transition: all .15s;
}
.co .pay-method.is-selected { border-color: var(--co-gold); background: #eef5fc; box-shadow: 0 0 0 1px var(--co-gold); }
.co .pay-method.is-selected .pay-method__check { background: var(--co-gold); border-color: var(--co-gold); color: #fff; }
.co .pay-method.is-selected .pay-method__mark { background: #d6e7f8; }

/* Order summary */
.co .checkout-aside { position: sticky; top: 20px; }
.co .summary {
    background: var(--co-card); border: 1px solid var(--co-line); border-top: 4px solid var(--co-gold);
    border-radius: var(--co-radius); padding: 24px 26px;
    box-shadow: 0 1px 2px rgba(20,33,61,.04), 0 12px 32px rgba(20,33,61,.07);
}
.co .summary h3 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 26px; font-weight: 700; margin: 0 0 14px; color: var(--co-ink); }
.co .checkout-summary-item {
    display: flex; justify-content: space-between; gap: 16px;
    padding: 10px 0; font-size: 14px; border-bottom: 1px dashed var(--co-line);
}
.co .checkout-summary-item span:last-child { font-weight: 600; white-space: nowrap; }
.co .summary-row { display: flex; justify-content: space-between; padding: 7px 0; font-size: 14.5px; color: var(--co-ink-soft); }
.co .summary-row.total {
    margin-top: 8px; padding-top: 14px; border-top: 2px solid var(--co-ink);
    font-size: 20px; font-weight: 700; color: var(--co-ink);
}
.co .small { font-size: 12.5px; line-height: 1.6; }
.co .muted { color: var(--co-ink-soft); }
.co .mt-2 { margin-top: 14px; }
.co .summary .small strong { color: var(--co-gold-dark); }

@media (max-width: 991px) {
    .co .checkout-layout { grid-template-columns: 1fr; }
    .co .checkout-aside { position: static; }
}
@media (max-width: 575px) {
    .co .form-grid { grid-template-columns: 1fr; }
    .co .checkout-panel { padding: 20px 18px; }
    .co .input-group { flex-direction: column; }
    .co .input-group .btn { width: 100%; }
    .co-banner__body { padding: 22px 22px; }
    .co-banner::before { background: linear-gradient(180deg, rgba(20,33,61,.88), rgba(20,33,61,.7)); }
}
@media (prefers-reduced-motion: reduce) { .co * { transition: none !important; } }
</style>

<div class="co"
     data-checkout-summary
     data-subtotal="<?= e((string) $subtotal) ?>"
     data-discount="<?= e((string) $discount) ?>"
     data-shipping-international="<?= e((string) $shippingRates['international']) ?>"
     data-shipping-domestic="<?= e((string) $shippingRates['domestic']) ?>"
     data-free-shipping="<?= $freeShipping ? '1' : '0' ?>"
     data-currency="<?= e(\App\Support\Site::currency() ?: 'USD') ?>">
<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb" aria-label="Breadcrumb">
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
    <!-- Festive discount banner -->
    <section class="co-banner" aria-label="Festive offer">
        <div class="co-banner__body">
            <span class="co-banner__tag">Festive offer</span>
            <h2 class="co-banner__title">Celebrate with <?= money(config('app.bulk_discount_amount')) ?> off and free shipping</h2>
            <p class="co-banner__text">
                Spend <?= money(config('app.bulk_discount_threshold')) ?> or more and use code
                <strong><?= e(\App\Support\Cart::promoCode()) ?></strong> in the discount box below.
            </p>
        </div>
    </section>

    <?php if ($errors !== []): ?>
        <div class="site-flash site-flash--error" role="alert">Please correct the highlighted fields and try again.</div>
    <?php endif; ?>

    <form action="<?= e(url('checkout')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="checkout-layout">
            <div class="checkout-main">
                <div class="checkout-panel">
                    <h2>Contact</h2>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="discount-code" class="required">Discount code</label>
                            <div class="input-group">
                                <input class="form-control" id="discount-code" name="code" type="text" placeholder="ROIDSSEEK2026" aria-label="Discount code" />
                                <button type="submit" name="apply_discount" formnovalidate class="btn btn-sm btn-primary">Apply</button>
                            </div>
                        </div>
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

                <?php
                $paymentMethods = \App\Support\PaymentMethod::all();
                $selectedPayment = \App\Support\PaymentMethod::normalise((string) old('payment_method', \App\Support\PaymentMethod::BITCOIN));
                $stripePublicKey = null;
                ?>
                <div class="checkout-panel checkout-panel--pay">
                    <h2>Payment method</h2>
                    <div class="pay-methods" role="radiogroup" aria-label="Payment method">
                        <?php foreach ($paymentMethods as $key => $option): ?>
                            <?php $selected = $key === $selectedPayment; ?>
                            <label class="pay-method<?= $selected ? ' is-selected' : '' ?>">
                                <input type="radio" name="payment_method" value="<?= e($key) ?>"<?= $selected ? ' checked' : '' ?>>
                                <span class="pay-method__mark" aria-hidden="true"><?= \App\Support\PaymentMethod::icon($key) ?></span>
                                <span class="pay-method__text">
                                    <strong><?= e($option['label']) ?> (<?= e($option['symbol']) ?>)</strong>
                                    <small>
                                        <?= e($option['blurb']) ?>
                                        Your exact amount and receive address appear on the payment page as soon as the order is placed.
                                    </small>
                                </span>
                                <span class="pay-method__check" aria-hidden="true">&#10003;</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errors['payment_method'])): ?><div class="field-error"><?= e($errors['payment_method']) ?></div><?php endif; ?>
                    <p class="small muted mt-2">
                        Choose your preferred cryptocurrency. You can switch between methods on the payment page before sending.
                        Orders move to processing once you confirm the transfer.
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
                        <div class="summary-row"><span>Discount<?= $coupon ? ' (' . e($coupon['code']) . ')' : '' ?></span><span>-<?= money($discount) ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row"><span>Shipping</span><span data-checkout-shipping><?= $shipping > 0 ? money($shipping) : 'Free' ?></span></div>
                    <div class="summary-row total"><span>Total</span><span data-checkout-total><?= money($total) ?></span></div>
                    <p class="small muted mt-2">
                        USA domestic <?= money(config('app.shipping_domestic_rate')) ?>,
                        international <?= money(config('app.shipping_international_rate')) ?>.
                        Orders of <?= money(config('app.bulk_discount_threshold')) ?> or more get
                        <?= money(config('app.bulk_discount_amount')) ?> off and free shipping with promo code
                        <strong><?= e(\App\Support\Cart::promoCode()) ?></strong>.
                    </p>
                    <button type="submit" name="place_order" formnovalidate class="btn btn-mix btn-primary btn-block">Place order</button>
                    <p class="small muted mt-2">By placing the order you agree to our terms of use.</p>
                    <?php if (isset($message) && $message !== ''): ?>
                        <div class="discount-message <?= str_contains((string) $message, 'success') ? 'discount-message--success' : 'discount-message--error' ?>">
                            <?= e($message) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </form>
</div>
</div>

<script>
(function () {
    var methods = document.querySelectorAll('.pay-method');
    methods.forEach(function (label) {
        var input = label.querySelector('input[type="radio"]');
        if (!input) { return; }
        input.addEventListener('change', function () {
            methods.forEach(function (other) {
                var i = other.querySelector('input[type="radio"]');
                other.classList.toggle('is-selected', !!(i && i.checked));
            });
        });
    });
})();

(function () {
    var root = document.querySelector('[data-checkout-summary]');
    var country = document.getElementById('shipping_country');
    if (!root || !country) { return; }

    var subtotal = parseFloat(root.getAttribute('data-subtotal')) || 0;
    var discount = parseFloat(root.getAttribute('data-discount')) || 0;
    var international = parseFloat(root.getAttribute('data-shipping-international')) || 0;
    var domestic = parseFloat(root.getAttribute('data-shipping-domestic')) || 0;
    var freeShipping = root.getAttribute('data-free-shipping') === '1';
    var currency = root.getAttribute('data-currency') || 'USD';
    var shippingEl = root.querySelector('[data-checkout-shipping]');
    var totalEl = root.querySelector('[data-checkout-total]');

    var formatter;
    try {
        formatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: currency });
    } catch (error) {
        formatter = null;
    }

    function money(value) {
        return formatter ? formatter.format(value) : value.toFixed(2) + ' ' + currency;
    }

    function isDomestic(value) {
        var normalised = String(value || '').toLowerCase().replace(/\s+/g, ' ').trim().replace(/\.$/, '');
        return ['us', 'u.s', 'usa', 'u.s.a', 'united states', 'united states of america'].indexOf(normalised) !== -1;
    }

    function update() {
        var shipping = freeShipping ? 0 : (isDomestic(country.value) ? domestic : international);
        if (shippingEl) { shippingEl.textContent = shipping > 0 ? money(shipping) : 'Free'; }
        if (totalEl) { totalEl.textContent = money(Math.max(0, subtotal - discount + shipping)); }
    }

    country.addEventListener('input', update);
    country.addEventListener('change', update);
})();
</script>