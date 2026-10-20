<?php
$homeCols = 'col-6 col-sm-4 col-md-3 col-lg-2';
$supportQuotes = array_slice($reviews, 0, 3);
?>
<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4 mt-4 home-hero">
            <svg width="0" height="0" style="position: absolute;" aria-hidden="true" focusable="false">
                <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"></path></symbol>
                <symbol id="i-check" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></symbol>
                <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></symbol>
                <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z"></path></symbol>
                <symbol id="i-star" viewBox="0 0 24 24"><path d="m12 3 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9z"></path></symbol>
            </svg>
            <div class="kit">
                <div class="kit-page">
                    <section class="kit-hero">
                        <div class="kit-hero__body">
                            <p class="kit-eyebrow"><svg class="icon" aria-hidden="true"><use href="#i-shield"></use></svg>RoidsSeek - Trusted Steroid &amp; Peptide Store</p>
                            <h1>Buy Anabolic Steroids, Peptides &amp; HGH Online</h1>
                            <p class="kit-hero__lede">Shop Pharmaceutical-Grade Anabolic Steroids and Premium Peptides for Recovery, Growth &amp; Performance</p>
                            <div class="kit-hero__actions">
                                <a class="kit-btn kit-btn--primary" href="<?= e(url('sale')) ?>">Shop Now<svg class="icon" aria-hidden="true"><use href="#i-arrow"></use></svg></a>
                                <a class="kit-btn kit-btn--quiet" href="<?= e(url('register')) ?>">Sign Up</a>
                            </div>
                            <div class="kit-hero__notes">
                                <span class="kit-pill"><svg class="icon" aria-hidden="true"><use href="#i-check"></use></svg><strong>US &amp; International Warehouse</strong></span>
                                <span class="kit-pill"><svg class="icon" aria-hidden="true"><use href="#i-clock"></use></svg><strong>Fast and Secure Delivery</strong></span>
                                <span class="kit-pill"><svg class="icon" aria-hidden="true"><use href="#i-star"></use></svg><strong>100%&nbsp;Customer Satisfaction</strong></span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="box-side">
    <div class="container">
        <div class="kit">
            <div class="kit-page">
                <section class="kit-section">
                    <div class="kit-section__head">
                        <div>
                            <p class="kit-eyebrow">DON'T MISS</p>
                            <h2>Current Promotions and Exclusive Deals</h2>
                            <p class="kit-section__sub">Save more with limited-time discounts, special offers and exclusive product promotions.</p>
                        </div>
                    </div>
                    <div class="kit-grid kit-grid--pair">
                        <a class="kit-banner" href="<?= e(url('sale')) ?>">
                            <img src="assets/img/discount-crypto.webp" alt="Pay with crypto, save up to 20%" width="1360" height="454" loading="lazy" decoding="async">
                        </a>
                        <a class="kit-banner" href="<?= e(url('category', ['slug' => 'peptides-sarms'])) ?>">
                            <img src="assets/img/peptides-discount.webp" alt="Save 40% on selected peptides" width="1360" height="454" loading="lazy" decoding="async">
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="box-side">
    <div class="container">
        <h2 class="box-side-h2 home-section-title"><strong><span style="color: #ba372a;">PROMOS and SALES</span></strong></h2>
    </div>
</div>
<div class="container">
    <div class="row frontpage-products">
        <?php foreach ($deals as $product): ?>
            <?= App\Support\View::partial('partials/product-card', ['product' => $product, 'cols' => $homeCols]) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="box-side">
    <div class="container">
        <h2 class="home-section-title"><strong>New Arrivals</strong></h2>
    </div>
</div>
<div class="container">
    <div class="row frontpage-products">
        <?php foreach ($featured as $product): ?>
            <?= App\Support\View::partial('partials/product-card', ['product' => $product, 'cols' => $homeCols]) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="box-side">
    <div class="container">
        <div class="kit" style="margin-top: 10px;">
            <div class="kit-page">
                <section class="kit-section">
                    <div class="kit-section__head">
                        <div>
                            <h2>Shipping Option</h2>
                        </div>
                    </div>
                    <div class="kit-grid kit-grid--pair">
                        <div class="kit-feature kit-feature--lg">
                            <h3>US Domestic Shipping</h3>
                            <p>Fast delivery within 3-5 business days.</p>
                            <a class="kit-feature__link" href="<?= e(url('shipping', ['slug' => 'usa'])) ?>">Go to US Domestic Shipping</a>
                        </div>
                        <div class="kit-feature kit-feature--lg">
                            <h3>International Shipping</h3>
                            <p>Save more with affordable delivery in 10-18 business days.</p>
                            <a class="kit-feature__link" href="<?= e(url('shipping', ['slug' => 'international'])) ?>">Go to International Shipping</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="box-side home-reviews">
    <div class="container">
        <h3>Customers Reviews</h3>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
                <div class="item col-lg-3 col-md-4 col-sm-6">
                    <a class="title" href="<?= e(url('product', ['slug' => $review['product_slug']])) ?>"><?= e($review['product_name']) ?></a>
                    <div>
                        By <span><?= e(strtoupper(substr($review['name'], 0, 1))) ?>***.</span>
                        On <?= e(date('F j, Y', strtotime($review['created_at']))) ?>
                    </div>
                    <div class="rating">
                        <div class="rating-conainer img-stars-<?= max(1, min(5, (int) $review['rating'])) ?>"></div>
                    </div>
                    <div class="info"><?= e(mb_strimwidth((string) $review['body'], 0, 180, '...')) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if ($reviews === []): ?>
                <div class="col-12 muted">No reviews yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="box-side">
    <div class="container">
        <div class="kit">
            <section class="kit-reviews">
                <div class="kit-reviews__body">
                    <h2>Thousands of Customers Trust RoidsSeek</h2>
                    <p class="kit-reviews__lede">Read independent reviews from trusted communities and verified review platforms.</p>
                </div>
                <div class="kit-reviews__grid">
                    <a class="kit-review-card" href="https://www.trustpilot.com/review/roidsseek.net" target="_blank" rel="noopener">
                        <img class="kit-review-card__logo" src="assets/img/trustpilot.svg" alt="TrustPilot Reviews">
                    </a>
                    <a class="kit-review-card" href="https://www.anabolicsteroidforums.com/forums/roidsseek.314/" target="_blank" rel="noopener">
                        <img class="kit-review-card__logo" src="assets/img/asf-logo.webp" alt="AnabolicSteroidForums">
                    </a>
                    <a class="kit-review-card" href="https://steroidwiki.com/sources/steroid-sources/roidsseek.com" target="_blank" rel="noopener">
                        <img class="kit-review-card__logo" src="assets/img/steroid-wiki.webp" alt="RoidsSeek SteroidWiki Reviews">
                    </a>
                    <a class="kit-review-card" href="https://www.eroids.com/reviews/roidsseek.to" target="_blank" rel="noopener">
                        <img class="kit-review-card__logo" src="assets/img/eroids-logo.webp" alt="RoidsSeek Eroids Reviews">
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>

<div class="box-side">
    <div class="container">
        <h2 class="home-section-title"><strong>RoidsSeek Best Sellers</strong></h2>
    </div>
</div>
<div class="container">
    <div class="row frontpage-products">
        <?php foreach (array_slice($featured, 0, 6) as $product): ?>
            <?= App\Support\View::partial('partials/product-card', ['product' => $product, 'cols' => $homeCols]) ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($events !== []): ?>
    <div class="box-side box-news box-news-bg" style="padding-bottom: 40px;">
        <div class="container">
            <h2 class="home-section-title">Latest News &amp; Updates</h2>
            <div class="row">
                <?php foreach ($events as $event): ?>
                    <div class="item col-md-4">
                        <a class="title" href="<?= e(url('events')) ?>" title="<?= e($event['title']) ?>">
                            <img src="<?= e(image_url($event['image'] ?? null)) ?>" alt="<?= e($event['title']) ?>" title="<?= e($event['title']) ?>" class="img-fluid">
                            <div class="info">
                                <span class="date"><?= e($event['published_at'] ? date('F j, Y', strtotime($event['published_at'])) : '') ?></span>
                            </div>
                            <strong><?= e($event['title']) ?></strong>
                        </a>
                        <p style="margin-bottom:0;"><?= e(mb_strimwidth((string) $event['excerpt'], 0, 180, '...')) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="row">
                <div class="col-12 d-flex justify-content-center">
                    <a class="btn btn-sm btn-primary" href="<?= e(url('events')) ?>" title="All Latest News &amp; Updates">All Latest News &amp; Updates</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4 mt-4">
            <div class="kit-prose info-content home-faq-prose">
                <h2>Buy Steroids Online at RoidsSeek - Premium Anabolic Steroids &amp; Peptides</h2>
                <p>RoidsSeek is a trusted online source for pharmaceutical-grade anabolic steroids, peptides, HGH, SARMs and PCT products. With more than 16 years of experience, we offer a wide selection of trusted brands, competitive prices, secure ordering, responsive customer support and fast worldwide shipping to customers around the globe.</p>
                <p>Whether you're focused on bulking, cutting, strength, recovery, or performance, you'll find high-quality products from leading manufacturers, including <a href="<?= e(url('brand', ['slug' => 'dragon-pharma'])) ?>">Dragon Pharma</a>, <a href="<?= e(url('brand', ['slug' => 'kalpa-pharmaceuticals'])) ?>">Kalpa Pharma</a>, <a href="<?= e(url('all')) ?>">British Dragon</a>, <a href="<?= e(url('brand', ['slug' => 'axiolabs'])) ?>">Axiolabs</a> and more. Our extensive catalog includes Testosterone, Trenbolone, Deca Durabolin, Sustanon, Anavar, Dianabol, Winstrol, Anadrol, Equipoise, growth hormone, recovery peptides, GLP-1 peptides and complete post cycle therapy (PCT) solutions.</p>
                <h2>Why Choose RoidsSeek?</h2>
                <ul>
                    <li>Over 16 years of industry experience</li>
                    <li>Wide selection of premium anabolic steroids, peptides, HGH and SARMs</li>
                    <li>Trusted pharmaceutical brands</li>
                    <li>No minimum order requirement</li>
                    <li>Fast, discreet and reliable worldwide shipping</li>
                    <li>Secure payment options</li>
                    <li>Responsive customer support</li>
                    <li>Thousands of satisfied customers worldwide</li>
                </ul>
                <p>At RoidsSeek, we're committed to providing genuine products, dependable service and a smooth shopping experience. Browse our extensive selection of performance-enhancing compounds and shop with confidence from one of the leading online bodybuilding stores.</p>
            </div>
        </div>
    </div>
</div>

<div class="box-online-support support-band">
    <div class="container text-center">
        <div class="default-section-description">
            <h2>Customers Reviews</h2>
            <p>Please leave your review on products or service below.<br> Thank you beforehand.</p>
            <ul>
                <?php foreach ($supportQuotes as $quote): ?>
                    <li><?= e(mb_strimwidth((string) $quote['body'], 0, 160, '...')) ?></li>
                <?php endforeach; ?>
                <?php if ($supportQuotes === []): ?>
                    <li>Very easy to order</li>
                    <li>Easy to navigate. Ordering is easy to understand.</li>
                <?php endif; ?>
            </ul>
            <a href="<?= e(url('all')) ?>" title="Write a Review" class="btn btn-mix btn-outline-light mr-sm-3 mt-3">Write a Review</a>
            <a href="<?= e(url('all')) ?>" title="View Reviews" class="btn btn-mix btn-outline-light mt-3">View Reviews</a>
        </div>
    </div>
</div>

<?php if ($articles !== []): ?>
    <div class="box-side box-news box-news-bg" style="padding-bottom: 40px;">
        <div class="container">
            <h2 class="home-section-title">Latest Articles &amp; Guides</h2>
            <div class="row">
                <?php foreach ($articles as $article): ?>
                    <div class="item col-md-4">
                        <a class="title" href="<?= e(url('article', ['slug' => $article['slug']])) ?>" title="<?= e($article['title']) ?>">
                            <img src="<?= e(image_url($article['image'] ?? null)) ?>" alt="<?= e($article['title']) ?>" title="<?= e($article['title']) ?>" class="img-fluid">
                            <div class="info">
                                <span class="date"><?= e($article['published_at'] ? date('F j, Y', strtotime($article['published_at'])) : '') ?></span>
                            </div>
                            <strong><?= e($article['title']) ?></strong>
                        </a>
                        <p style="margin-bottom:0;"><?= e(mb_strimwidth((string) $article['excerpt'], 0, 180, '...')) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
