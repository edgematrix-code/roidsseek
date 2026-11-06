<?php
/**
 * Floating cart modal.
 *
 * Opens after a product is added to the cart and stays open until the customer
 * closes it. Each item follows the same card layout: image on the left, details
 * on the right (title + price, brand badge, specs, delivery toggle, quantity),
 * then a footer with the order total and the two actions.
 *
 * The delivery toggle re-prices the basket on the server: International is the
 * cheaper service, US domestic costs 20% more, and both drop to free once the
 * order reaches the free-shipping threshold.
 */
$items = \App\Support\Cart::items();
$count = \App\Support\Cart::count();
$total = \App\Support\Cart::total();
$service = \App\Support\Cart::shippingService();
$shipping = \App\Support\Cart::shipping();
$coupon = \App\Support\Cart::coupon();

$open = !empty($opensModal) || !empty($_SESSION['cart_modal_open']);

// Closed: render nothing at all. Only the page that lands after "add to cart"
// (or an AJAX re-price) needs the markup, and this keeps the product query off
// every other page.
if (!$open) {
    return;
}
unset($_SESSION['cart_modal_open']);

$lastItem = $items === [] ? null : $items[array_key_last($items)];

// Item prices are shown delivered, priced exactly the way the product page
// prices them: unit price + the delivery fee for the selected service, with the
// fee dropped once free shipping applies. Reusing that rule keeps the price a
// customer saw on the product page identical to the price in this modal, and
// switching International / U.S. Domestic moves both to the same number.
$rates = \App\Support\Cart::shippingRates();
$deliveryFees = [];
foreach ($items as $key => $row) {
    $product = $row['product'];

    // Only the services that actually ship this product are offered; when the
    // selected one is not available the page falls back to the first that is.
    $location = (string) ($product['shipping_location'] ?? 'both');
    $options = [];
    if ($location !== 'usa') {
        $options['international'] = (float) $rates['international'];
    }
    if ($location !== 'international') {
        $options['domestic'] = (float) $rates['domestic'];
    }
    if ($options === []) {
        $options['international'] = (float) $rates['international'];
    }
    $rowService = isset($options[$service]) ? $service : (string) array_key_first($options);

    // Free delivery is part of the promo offer, so it only applies while the
    // promo code is on the cart - never because a single item is expensive.
    $free = \App\Support\Cart::hasFreeShipping();

    $deliveryFees[$key] = $free ? 0.0 : $options[$rowService];
}
?>
<div class="cart-modal is-open" id="cart-modal" aria-hidden="false">
    <div class="cart-modal__backdrop" data-cart-close></div>
    <div class="cart-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cart-modal-title">
        <header class="cart-modal__head">
            <h2 class="cart-modal__title" id="cart-modal-title">
                Add to Cart &ndash; <?= (int) $count ?> Product(s)
            </h2>
            <a class="cart-modal__close" href="<?= e(url('cart/modal/close')) ?>" data-cart-close aria-label="Close cart">
                <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true" focusable="false">
                    <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                </svg>
            </a>
        </header>

        <div class="cart-modal__body">
            <?php if ($items === []): ?>
                <p class="cart-modal__empty">Empty</p>
            <?php else: ?>
                <?php foreach ($items as $itemKey => $row): $product = $row['product']; ?>
                    <?php
                    // products.manufacturer is optional; the brand is the usual
                    // source for this line on products that only carry one.
                    $manufacturer = trim((string) ($product['manufacturer'] ?? '')) !== ''
                        ? (string) $product['manufacturer']
                        : (string) ($product['brand_name'] ?? '');
                    ?>
                    <article class="cart-modal__item">
                        <div class="cart-modal__media">
                            <img src="<?= e(image_url($product['image'] ?? null)) ?>" alt="<?= e($product['name']) ?>"
                                 width="120" height="120" loading="lazy" decoding="async">
                        </div>

                        <div class="cart-modal__details">
                            <div class="cart-modal__top">
                                <h3 class="cart-modal__name">
                                    <a href="<?= e(url('product', ['slug' => $product['slug']])) ?>"><?= e($product['name']) ?></a>
                                </h3>
                                <span class="cart-modal__price" data-line-price><?= money((float) $row['line_total'] + ($deliveryFees[$itemKey] ?? 0.0)) ?></span>
                            </div>

                            <?php if ($manufacturer !== ''): ?>
                                <span class="cart-modal__badge"><?= e($manufacturer) ?></span>
                            <?php endif; ?>

                            <ul class="cart-modal__specs">
                                <?php if ($manufacturer !== ''): ?>
                                    <li>
                                        <span class="cart-modal__spec-label">Manufacturer:</span>
                                        <span><?= e($manufacturer) ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if (!empty($product['pharmaceutical_name']) || !empty($product['substance'])): ?>
                                    <li>
                                        <span class="cart-modal__spec-label">Pharmaceutical name:</span>
                                        <span><?= e($product['pharmaceutical_name'] ?: $product['substance']) ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if (!empty($product['pack'])): ?>
                                    <li>
                                        <span class="cart-modal__spec-label">Pack:</span>
                                        <span><?= e($product['pack']) ?><?= !empty($product['strength']) ? ' (' . e($product['strength']) . ')' : '' ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div class="cart-modal__shipping">
                                <span class="cart-modal__shipping-label">Shipping:</span>
                                <form class="cart-modal__services js-cart-service" method="post" action="<?= e(url('cart/shipping')) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" name="service" value="international"
                                            class="btn btn-sm <?= $service === 'international' ? 'btn-outline-primary is-active' : 'btn-outline-secondary' ?>">International</button>
                                    <button type="submit" name="service" value="domestic"
                                            class="btn btn-sm <?= $service === 'domestic' ? 'btn-outline-primary is-active' : 'btn-outline-secondary' ?>">U.S. Domestic</button>
                                </form>
                            </div>

                            <div class="cart-modal__qty-row">
                                <form class="cart-modal__qty js-cart-qty" method="post" action="<?= e(url('cart/update')) ?>">
                                    <?= csrf_field() ?>
                                    <div class="cart-modal__stepper">
                                        <button type="button" class="cart-modal__step" data-cart-delta="-1" aria-label="Decrease quantity">&minus;</button>
                                        <input class="cart-modal__qty-input" type="number" name="qty[<?= (int) $product['id'] ?>]"
                                               value="<?= (int) $row['qty'] ?>" min="0" max="<?= max(1, (int) $product['stock']) ?>"
                                               aria-label="Quantity for <?= e($product['name']) ?>">
                                        <button type="button" class="cart-modal__step" data-cart-delta="1" aria-label="Increase quantity">+</button>
                                    </div>
                                    <button type="submit" class="cart-modal__qty-submit">Update</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($lastItem !== null): ?>
            <?php
            $subtotal = \App\Support\Cart::subtotal();
            $discount = \App\Support\Cart::discount();
            $deliveryFee = \App\Support\Cart::shipping();
            $serviceLabel = $service === \App\Support\Cart::SERVICE_DOMESTIC ? 'U.S. Domestic' : 'International';
            ?>
            <footer class="cart-modal__foot">
                <div class="cart-modal__summary">
                    <div class="cart-modal__summary-row">
                        <span>Subtotal</span>
                        <span><?= money($subtotal) ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                        <div class="cart-modal__summary-row">
                            <span>Discount<?= $coupon ? ' (' . e($coupon['code']) . ')' : '' ?></span>
                            <span>&minus;<?= money($discount) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="cart-modal__summary-row" data-shipping-row>
                        <span>Shipping (<?= e($serviceLabel) ?>)</span>
                        <span data-shipping-cost><?= $deliveryFee > 0 ? money($deliveryFee) : 'Free' ?></span>
                    </div>
                </div>

                <div class="cart-modal__total-row">
                    <span>Total Cost:</span>
                    <span class="cart-modal__total"><?= money($total) ?></span>
                </div>

                <div class="cart-modal__actions">
                    <form class="cart-modal__add" method="post" action="<?= e(url('cart/add')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $lastItem['product']['id'] ?>">
                        <input type="hidden" name="qty" value="1">
                        <button type="submit" class="btn btn-outline-primary">Add to Cart</button>
                    </form>
                    <a class="btn btn-primary" href="<?= e(url('checkout')) ?>">Checkout</a>
                </div>
            </footer>
        <?php endif; ?>
    </div>
</div>
