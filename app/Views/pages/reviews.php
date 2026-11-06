<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Customer Reviews</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <h1 class="h1">Customer Reviews</h1>
                <div class="info" style="text-align:center;font-size:12px;color:#9e9fa1;padding-bottom:24px;">
                    <div class="reading-time">What our customers say</div>
                </div>

                <!-- Before & After Images -->
                <div class="reviews-section">
                    <h2 class="reviews-section-title">Before &amp; After</h2>
                    <p class="reviews-section-sub">Real customer results shared with us.</p>

                    <div class="before-after-grid">
                        <?php if (!empty($testimonies)): ?>
                            <?php foreach ($testimonies as $index => $testimony): ?>
                                <div class="before-after-item">
                                    <div class="before-after-labels">
                                        <span class="label label-before">Before</span>
                                        <span class="label label-after">After</span>
                                    </div>
                                    <div class="before-after-images">
                                        <img src="<?= e(asset('Reviews-Images/' . basename($testimony))) ?>" alt="Before and after result <?= $index + 1 ?>" class="before-after-img">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="muted">No testimony images available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Testimony Image -->
                <?php if ($testimonyTwo): ?>
                <div class="reviews-section">
                    <h2 class="reviews-section-title">Customer Testimonial</h2>
                    <div class="testimony-wrapper">
                        <img src="<?= e(asset('Reviews-Images/' . basename($testimonyTwo))) ?>" alt="Customer testimonial" class="testimony-img">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Discount Image -->
                <?php if ($discountImage): ?>
                <div class="reviews-section">
                    <h2 class="reviews-section-title">Special Offers</h2>
                    <div class="discount-wrapper">
                        <img src="<?= e(asset('Reviews-Images/' . basename($discountImage))) ?>" alt="Special discount offer" class="discount-img">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Chat Screenshots (individual, no before/after labels) -->
                <div class="reviews-section">
                    <h2 class="reviews-section-title">Customer Chat Screenshots</h2>
                    <p class="reviews-section-sub">Real conversations with our support team.</p>

                    <div class="chat-screenshots-grid">
                        <?php foreach ($chatScreenshots as $index => $chat): ?>
                            <div class="chat-screenshot-item">
                                <img src="<?= e(asset('Reviews-Images/' . basename($chat))) ?>" alt="Customer chat screenshot <?= $index + 1 ?>" class="chat-screenshot-img">
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($chatScreenshots)): ?>
                            <p class="muted">No chat screenshots available.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* Reviews Page Styles */
    .reviews-section {
        margin-bottom: 48px;
        padding: 24px 0;
        border-top: 1px solid #eee;
    }

    .reviews-section:first-child {
        border-top: none;
        padding-top: 0;
    }

    .reviews-section-title {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 8px;
        text-align: center;
    }

    .reviews-section-sub {
        text-align: center;
        color: #666;
        font-size: 14px;
        margin-bottom: 24px;
    }

    /* Before & After Grid */
    .before-after-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .before-after-item {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s ease;
    }

    .before-after-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .before-after-labels {
        display: flex;
        justify-content: space-between;
        padding: 8px 12px;
        background: #f8f9fa;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .label-before {
        color: #8b0000;
        background: #ffe8e8;
        padding: 3px 10px;
        border-radius: 4px;
    }

    .label-after {
        color: #006400;
        background: #e8ffe8;
        padding: 3px 10px;
        border-radius: 4px;
    }

    .before-after-images {
        padding: 8px;
    }

    .before-after-img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 4px;
        max-height: 300px;
        object-fit: cover;
    }

    /* Testimony Image */
    .testimony-wrapper {
        text-align: center;
        padding: 16px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .testimony-img {
        max-width: 100%;
        height: auto;
        max-height: 400px;
        object-fit: contain;
        border-radius: 4px;
    }

    /* Discount Image */
    .discount-wrapper {
        text-align: center;
        padding: 16px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .discount-img {
        max-width: 100%;
        height: auto;
        max-height: 350px;
        object-fit: contain;
        border-radius: 4px;
    }

    /* Chat Screenshots Grid */
    .chat-screenshots-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .chat-screenshot-item {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s ease;
    }

    .chat-screenshot-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .chat-screenshot-img {
        width: 100%;
        height: auto;
        display: block;
        max-height: 400px;
        object-fit: contain;
    }

    @media (max-width: 768px) {
        .before-after-grid,
        .chat-screenshots-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
