<?php
/**
 * First-visit modals.
 *
 * Rendered on every public page but hidden; assets/js/ui.js decides whether to
 * show it. The sequence is deliberate: the 18+ age gate comes first and cannot
 * be dismissed without a choice, the festive promo follows once the visitor has
 * confirmed, and a visitor who says they are under age gets a blocking notice
 * instead of the offer. Both decisions are remembered per browser in
 * localStorage so the pair appears once, not on every page load.
 *
 * The promo copy is built from config so the modal can never drift from the
 * coupon the checkout actually honours.
 */
$promoCode = strtoupper(trim((string) config('app.promo_code')));
$promoAmount = (float) config('app.bulk_discount_amount');
$promoMinimum = (float) config('app.bulk_discount_threshold');
$money = static fn (float $value): string => '$' . number_format($value, 0, '.', ',');
?>
<div class="visit-modals" id="visit-modals" hidden>
    <div class="visit-modals__backdrop" data-visit-backdrop></div>

    <section class="visit-modal visit-modal--age" id="age-gate" role="dialog" aria-modal="true"
             aria-labelledby="age-gate-title" aria-describedby="age-gate-copy">
        <span class="visit-modal__badge">18+ only</span>
        <h2 class="visit-modal__title" id="age-gate-title">You must be 18 years of age or older to enter.</h2>
        <p class="visit-modal__copy" id="age-gate-copy">Anabolic steroids can be dangerous and should be used with caution.</p>
        <p class="visit-modal__note">Products are for research and educational use only. Not medical advice.</p>
        <div class="visit-modal__actions">
            <button type="button" class="visit-btn visit-btn--primary" data-age-accept>I am 18 or older</button>
            <button type="button" class="visit-btn visit-btn--ghost" data-age-deny>I am under 18</button>
        </div>
    </section>

    <section class="visit-modal visit-modal--promo" id="promo-modal" role="dialog" aria-modal="true"
             aria-labelledby="promo-title" aria-describedby="promo-copy" hidden>
        <button type="button" class="visit-modal__close" data-promo-close aria-label="Close offer">
            <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true" focusable="false">
                <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
            </svg>
        </button>
        <div class="visit-modal__media">
            <img src="<?= e(asset('images/discount-image.jpg')) ?>"
                 alt="RoidsSeek festive discounts" width="1024" height="1024" decoding="async">
        </div>
        <div class="visit-modal__body">
            <p class="visit-modal__eyebrow">Festive offer</p>
            <h2 class="visit-modal__title" id="promo-title"><?= e($money($promoAmount)) ?> off and free shipping</h2>
            <div class="visit-modal__code">
                <span class="visit-modal__code-label">Promo code</span>
                <code><?= e($promoCode) ?></code>
                <button type="button" class="visit-btn visit-btn--copy" data-promo-copy data-copied="Copied">Copy</button>
            </div>
            <p class="visit-modal__copy" id="promo-copy">
                Enter the code at checkout on an order of <?= e($money($promoMinimum)) ?> or more to take
                <?= e($money($promoAmount)) ?> off and get free shipping. The price updates as soon as you apply it.
            </p>
            <div class="visit-modal__actions">
                <a class="visit-btn visit-btn--primary" href="<?= e(url('sale')) ?>">Shop the offer</a>
                <a class="visit-btn visit-btn--ghost" href="<?= e(url('cart')) ?>">Go to cart</a>
                <button type="button" class="visit-btn visit-btn--ghost" data-promo-close data-show-reviews="1">See Reviews</button>
            </div>
        </div>
    </section>

    <section class="visit-modal visit-modal--reviews" id="reviews-modal" role="dialog" aria-modal="true"
             aria-labelledby="reviews-title" hidden>
        <button type="button" class="visit-modal__close" data-reviews-close aria-label="Close reviews">
            <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true" focusable="false">
                <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
            </svg>
        </button>
        <div class="visit-modal__body reviews-modal-body">
            <p class="visit-modal__eyebrow">What our customers say</p>
            <h2 class="visit-modal__title" id="reviews-title">Real Results from Real Customers</h2>

            <div class="reviews-modal-before-after">
                <div class="reviews-modal-ba-item">
                    <div class="reviews-modal-labels">
                        <span class="label label-before">Before</span>
                        <span class="label label-after">After</span>
                    </div>
                    <img src="<?= e(asset('Reviews-Images/testimony-1.jpg')) ?>" alt="Customer before and after results" class="reviews-modal-img">
                </div>
                <div class="reviews-modal-ba-item">
                    <div class="reviews-modal-labels">
                        <span class="label label-before">Before</span>
                        <span class="label label-after">After</span>
                    </div>
                    <img src="<?= e(asset('Reviews-Images/testimony-3.jpg')) ?>" alt="Customer before and after results" class="reviews-modal-img">
                </div>
                <div class="reviews-modal-ba-item">
                    <div class="reviews-modal-labels">
                        <span class="label label-before">Before</span>
                        <span class="label label-after">After</span>
                    </div>
                    <img src="<?= e(asset('Reviews-Images/testimony-4.jpg')) ?>" alt="Customer before and after results" class="reviews-modal-img">
                </div>
            </div>

            <div class="reviews-modal-cta">
                <a href="<?= e(url('reviews')) ?>" class="visit-btn visit-btn--primary visit-btn--block">See More Reviews</a>
            </div>
        </div>
    </section>

    <style>
        .reviews-modal-body {
            text-align: center;
            padding: 8px;
        }

        .reviews-modal-before-after {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 16px 0;
            flex-wrap: wrap;
        }

        .reviews-modal-ba-item {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            width: 140px;
        }

        .reviews-modal-labels {
            display: flex;
            justify-content: space-between;
            padding: 4px 8px;
            background: #f8f9fa;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .label-before {
            color: #8b0000;
            background: #ffe8e8;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .label-after {
            color: #006400;
            background: #e8ffe8;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .reviews-modal-img {
            width: 100%;
            height: auto;
            display: block;
            max-height: 120px;
            object-fit: cover;
        }

        .reviews-modal-cta {
            margin-top: 16px;
        }

        .visit-btn--block {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px 20px;
        }

        @media (max-width: 480px) {
            .reviews-modal-before-after {
                flex-direction: column;
                align-items: center;
            }

            .reviews-modal-ba-item {
                width: 100%;
                max-width: 200px;
            }
        }
    </style>

    </section>

    <section class="visit-modal visit-modal--denied" id="age-denied" role="dialog" aria-modal="true"
             aria-labelledby="denied-title" hidden>
        <span class="visit-modal__badge visit-modal__badge--muted">Adults only</span>
        <h2 class="visit-modal__title" id="denied-title">You must be 18 or older to shop here.</h2>
        <p class="visit-modal__copy">This store is not available to visitors under the legal age. Come back when you are 18 or older.</p>
        <div class="visit-modal__actions">
            <a class="visit-btn visit-btn--ghost" href="https://www.google.com/">Leave site</a>
        </div>
    </section>
</div>
