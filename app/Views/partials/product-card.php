<?php
/** @var array $product */
$link = url('product', ['slug' => $product['slug']]);
$brandName = $product['brand_name'] ?? '';
$cols = $cols ?? 'col-6 col-sm-4 col-md-4 col-xl-3';
$inStock = (int) ($product['stock'] ?? 0) > 0;
$oldPrice = old_price_active($product) ? (float) $product['old_price'] : null;
?>
<div class="items-prod<?= $inStock ? '' : ' items-prod-out-of-stock' ?> <?= e($cols) ?> items-prod-col"
     data-manufacture="<?= e($brandName) ?>"
     data-price="<?= e((string) $product['price']) ?>"
     data-substance="<?= e((string) ($product['substance'] ?? '')) ?>"
     data-title="<?= e($product['name']) ?>">
    <div class="js-prod-detail prod-detail" data-id="prod-detail-<?= (int) $product['id'] ?>">
        <?php if (!empty($product['ribbon'])): ?>
            <div class="ribbon-wrapper" style="top: 15px;">
                <div class="ribbon" style="color:#fcf9f9;background-color:#fc0606;">
                    <span><?= e($product['ribbon']) ?></span>
                </div>
            </div>
        <?php elseif ($oldPrice !== null): ?>
            <div class="ribbon-wrapper" style="top: 15px;">
                <div class="ribbon">
                    <span>-<?= (int) round(100 - ((float) $product['price'] / $oldPrice * 100)) ?>% OFF</span>
                </div>
            </div>
        <?php endif; ?>
        <div class="box-product-descr">
            <a class="img" href="<?= e($link) ?>">
                <img decoding="async" loading="lazy" class="img-fluid" width="256" height="256"
                     src="<?= e(image_url($product['image'] ?? null)) ?>"
                     alt="<?= e($product['name']) ?>" title="<?= e($product['name']) ?>">
            </a>
            <div class="info-desc">
                <div class="info-desc-left">
                    <?php if ($brandName !== ''): ?>
                        <div class="product-flags-align"><?= e($brandName) ?></div>
                    <?php endif; ?>
                    <div class="box-title">
                        <a href="<?= e($link) ?>" class="title-main-link"><?= e($product['name']) ?></a>
                    </div>
                    <div class="short-text">
                        <p>
                            <?php if ($brandName !== ''): ?>
                                <strong>Manufacturer:</strong> <?= e($brandName) ?><br>
                            <?php endif; ?>
                            <?php if (!empty($product['pharmaceutical_name']) || !empty($product['substance'])): ?>
                                <strong>Pharmaceutical name:</strong> <?= e($product['pharmaceutical_name'] ?: $product['substance']) ?><br>
                            <?php endif; ?>
                            <?php if (!empty($product['pack'])): ?>
                                <strong>Pack:</strong> <?= e($product['pack']) ?><?= !empty($product['strength']) ? ' (' . e($product['strength']) . ')' : '' ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="info-desc-right">
                    <div class="price-info">
                        <span class="box-price" id="offer-sale-price-<?= (int) $product['id'] ?>" data-value="<?= e((string) $product['price']) ?>" data-currency="USD">
                            <?= money($product['price']) ?>
                        </span>
                        <?php if ($oldPrice !== null): ?>
                            <small class="box-old-price" id="offer-init-price-<?= (int) $product['id'] ?>">
                                <s><?= money($oldPrice) ?></s>
                            </small>
                        <?php else: ?>
                            <small class="box-old-price" id="offer-init-price-<?= (int) $product['id'] ?>"></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($inStock): ?>
                        <form class="f-prod-action add-cart-form" action="<?= e(url('cart/add')) ?>" method="post" id="add-to-cart-<?= (int) $product['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <input type="hidden" name="qty" value="1">
                            <div class="add-cart-form-bl">
                                <button class="btn-buy" type="submit">
                                    <em class="btn btn-sm btn-primary btn-add-cart">Add to Cart</em>
                                    <i class="shopping-cart">
                                        <svg width="16" height="21" viewBox="0 0 16 21" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <g fill="#FFF" fill-rule="nonzero">
                                                    <path d="M15.5,5.3 L12,5.3 L12,4.4 C12,2.1 10.2,0.2 8,0.2 C5.8,0.2 4,2.1 4,4.4 L4,5.3 L0.5,5.3 C0.2,5.3 0,5.5 0,5.8 L0,20.4 C0,20.7 0.2,20.9 0.5,20.9 L15.5,20.9 C15.8,20.9 16,20.7 16,20.4 L16,5.8 C16,5.5 15.8,5.3 15.5,5.3 Z M5,4.4 C5,2.7 6.4,1.2 8.1,1.2 C9.7,1.2 11,2.7 11,4.4 L11,5.3 L5,5.3 L5,4.4 Z M15,19.8 L1,19.8 L1,6.3 L4,6.3 L4,7.8 C4,8 4.2,8.2 4.5,8.2 C4.8,8.2 5,8 5,7.7 L5,6.3 L11,6.3 L11,7.8 C11,8.1 11.2,8.3 11.5,8.3 C11.8,8.3 12,8 12,7.7 L12,6.3 L15,6.3 L15,19.8 Z"></path>
                                                </g>
                                            </g>
                                        </svg>
                                    </i>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="add-cart-form-bl">
                            <div class="no-product">
                                <em>Out of stock</em>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
