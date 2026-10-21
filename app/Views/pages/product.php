<?php
$avgRating = $rating && $rating['avg_rating'] !== null ? (float) $rating['avg_rating'] : 0.0;
$reviewCount = $rating ? (int) $rating['total'] : 0;
$primaryCategory = $categories[0] ?? null;
$inStock = (int) $product['stock'] > 0;
$oldPrice = old_price_active($product) ? (float) $product['old_price'] : null;
$rxRequired = ($product['rx_status'] ?? '') === 'rx';
$restricted = !empty($product['is_restricted']);
$imageAlt = trim((string) ($product['image_alt'] ?? '')) !== '' ? (string) $product['image_alt'] : (string) $product['name'];
$editorial = (array) \App\Support\Site::get('editorial', []);
$storeName = \App\Support\Site::name();
?>
<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <?php if ($primaryCategory): ?>
                            <li><a href="<?= e(url('category', ['slug' => $primaryCategory['slug']])) ?>"><span><?= e($primaryCategory['name']) ?></span></a></li>
                        <?php endif; ?>
                        <li><span><?= e($product['name']) ?></span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-cat-select">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="h1 category-title"><?= e($product['name']) ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="container product-detail-wrap">
    <div class="product-detail-row">
        <div class="product-media">
            <span class="product-cover">
                <img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($imageAlt) ?>"
                     width="600" height="600" fetchpriority="high" decoding="async">
            </span>
        </div>
        <div class="product-information">
            <?php if (!empty($product['brand_name'])): ?>
                <div class="brand-line">
                    Brand: <a href="<?= e(url('brand', ['slug' => $product['brand_slug']])) ?>"><?= e($product['brand_name']) ?></a>
                    &middot; SKU: <?= e($product['sku']) ?>
                </div>
            <?php else: ?>
                <div class="brand-line">SKU: <?= e($product['sku']) ?></div>
            <?php endif; ?>

            <?php if ($reviewCount > 0): ?>
                <div class="brand-line">
                    <span class="rating-conainer img-stars-<?= max(1, min(5, (int) round($avgRating))) ?>"></span>
                    <?= number_format($avgRating, 1) ?> / 5 (<?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>)
                </div>
            <?php endif; ?>

            <?php if ($rxRequired): ?>
                <p class="rx-notice"><strong>Prescription required.</strong>
                    This medicine is supplied only against a valid prescription from a licensed prescriber.
                </p>
            <?php endif; ?>

            <div class="product-prices">
                <span class="box-price"><?= money($product['price']) ?></span>
                <?php if ($oldPrice !== null): ?>
                    <span class="box-old-price"><?= money($oldPrice) ?></span>
                    <span class="save">You save <?= money($oldPrice - (float) $product['price']) ?></span>
                <?php endif; ?>
            </div>

            <table class="product-specs">
                <?php if (!empty($product['substance'])): ?>
                    <tr><th>Substance</th><td><?= e($product['substance']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['pharmaceutical_name'])): ?>
                    <tr><th>Pharmaceutical name</th><td><?= e($product['pharmaceutical_name']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['active_ingredient'])): ?>
                    <tr><th>Active ingredient</th><td><?= e($product['active_ingredient']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['dosage_form'])): ?>
                    <tr><th>Dosage form</th><td><?= e($product['dosage_form']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['manufacturer'])): ?>
                    <tr><th>Manufacturer</th><td><?= e($product['manufacturer']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['registration_number'])): ?>
                    <tr><th>Registration number</th><td><?= e($product['registration_number']) ?></td></tr>
                <?php endif; ?>
                <?php if (($product['rx_status'] ?? '') === 'otc' || $rxRequired): ?>
                    <tr><th>Prescription</th><td><?= $rxRequired ? 'Prescription required' : 'Over the counter' ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($product['pack'])): ?>
                    <tr><th>Pack</th><td><?= e($product['pack']) ?><?= !empty($product['strength']) ? ' (' . e($product['strength']) . ')' : '' ?></td></tr>
                <?php endif; ?>
                <tr>
                    <th>Shipping</th>
                    <td>
                        <?= $product['shipping_location'] === 'usa' ? 'USA domestic'
                            : ($product['shipping_location'] === 'international' ? 'International' : 'USA domestic &amp; International') ?>
                    </td>
                </tr>
                <tr>
                    <th>Availability</th>
                    <td>
                        <span class="stock-pill <?= $inStock ? '' : 'out' ?>">
                            <?= $inStock ? 'In stock (' . (int) $product['stock'] . ')' : 'Out of stock' ?>
                        </span>
                    </td>
                </tr>
            </table>

            <?php if ($inStock): ?>
                <form action="<?= e(url('cart/add')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <div class="product-quantity">
                        <label for="qty" class="small muted">Qty</label>
                        <input id="qty" type="number" name="qty" value="1" min="1" max="<?= (int) $product['stock'] ?>">
                        <button type="submit" class="btn btn-mix btn-primary">Add to Cart</button>
                        <button type="submit" name="return_to" value="cart" class="btn btn-mix btn-outline-primary">Buy Now</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="muted">
                    This product is temporarily out of stock.
                    <a href="<?= e(url('contact', ['subject' => 'Notify me: ' . $product['name']])) ?>">Ask to be notified when it returns</a>.
                </p>
                <?php if ($categories !== []): ?>
                    <p class="small">
                        Similar products:
                        <?php foreach (array_slice($categories, 0, 2) as $category): ?>
                            <a href="<?= e(url('category', ['slug' => $category['slug']])) ?>"><?= e($category['name']) ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <p class="small muted mt-2">
                Free shipping on orders over <?= money(config('app.free_shipping_threshold')) ?>.
                Discreet packaging and tracking on every order.
            </p>
        </div>
    </div>
</div>

<div class="container detail-tabs">
    <ul class="detail-nav-tab">
        <li><a class="active" href="#details">Description</a></li>
        <li><a href="#reviews">Reviews <?= $reviewCount > 0 ? '(' . $reviewCount . ')' : '' ?></a></li>
        <li><a href="#write-review">Write a review</a></li>
    </ul>

    <div class="detail-panel" id="details">
        <h2>Description</h2>
        <?php $productText = trim((string) ($product['description'] ?: $product['short_description'])); ?>
        <?php if ($productText !== ''): ?>
            <p><?= nl2br(e($productText)) ?></p>
        <?php else: ?>
            <p class="muted">No product description has been supplied for this item yet. Descriptions are published only from manufacturer material or copy approved by the store owner.</p>
        <?php endif; ?>
        <?php if ($goals !== []): ?>
            <p class="mt-2">
                Popular for:
                <?php foreach ($goals as $index => $goal): ?>
                    <?= $index > 0 ? ', ' : '' ?><a href="<?= e(url('goal', ['slug' => $goal['slug']])) ?>"><?= e($goal['name']) ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="detail-panel medical-disclaimer">
        <h2>Medical information</h2>
        <p>
            This page provides product and ordering information only and is not medical advice.
            Always read the manufacturer's leaflet and consult a qualified healthcare professional
            before using any medicine. Content is added only after review by a licensed pharmacist.
        </p>
        <?php if (!empty($editorial['reviewerName'])): ?>
            <p class="small muted">
                Medical reviewer: <?= e((string) $editorial['reviewerName']) ?>
                <?php if (!empty($editorial['reviewerCredentials'])): ?>, <?= e((string) $editorial['reviewerCredentials']) ?><?php endif; ?>
            </p>
        <?php endif; ?>
        <p class="small muted">
            <a href="<?= e(url('page', ['slug' => 'medical-disclaimer'])) ?>">Medical disclaimer</a>
            <?php if ($storeName !== ''): ?> &middot; <?= e($storeName) ?><?php endif; ?>
        </p>
    </div>

    <div class="detail-panel" id="reviews">
        <h2>Customer reviews</h2>
        <?php if ($reviews === []): ?>
            <p class="muted">No reviews yet. Be the first to review this product.</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-head">
                        <span class="rating-conainer img-stars-<?= max(1, min(5, (int) $review['rating'])) ?>"></span>
                        <span><?= e(date('M j, Y', strtotime($review['created_at']))) ?></span>
                    </div>
                    <h4><?= e($review['title'] ?: 'Verified customer') ?> &middot; <?= e($review['name']) ?></h4>
                    <p><?= nl2br(e($review['body'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="detail-panel" id="write-review">
        <h2>Write a review</h2>
        <form action="<?= e(url('review', ['slug' => $product['slug']])) ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="review-name">Your name</label>
                    <input class="form-control" id="review-name" type="text" name="name" value="<?= e(user()['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="review-rating">Rating</label>
                    <select class="form-control" id="review-rating" name="rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label for="review-title">Title</label>
                    <input class="form-control" id="review-title" type="text" name="title" placeholder="Summarise your experience">
                </div>
                <div class="form-group full">
                    <label for="review-body">Review</label>
                    <textarea class="form-control" id="review-body" name="body" required placeholder="How did the product work for you?"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-mix btn-primary">Submit review</button>
        </form>
    </div>
</div>

<?php if ($related !== []): ?>
    <div class="container">
        <h2 class="home-section-title"><strong>You may also like</strong></h2>
        <div class="row frontpage-products">
            <?php foreach ($related as $item): ?>
                <?= App\Support\View::partial('partials/product-card', ['product' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div style="height:30px;"></div>
<?php endif; ?>
