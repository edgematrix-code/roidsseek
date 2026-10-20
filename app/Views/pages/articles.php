<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Articles</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data page-story">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h1">Articles</h1>
            </div>
            <?php foreach ($articles as $article): ?>
                <div class="item col-md-6">
                    <a href="<?= e(url('article', ['slug' => $article['slug']])) ?>" title="<?= e($article['title']) ?>">
                        <img src="<?= e(image_url($article['image'] ?? null)) ?>" alt="<?= e($article['title']) ?>" title="<?= e($article['title']) ?>" class="img-fluid">
                    </a>
                    <div class="news-desc">
                        <div class="info">
                            <span class="date"><?= e($article['published_at'] ? date('M j, Y', strtotime($article['published_at'])) : '') ?></span>
                        </div>
                        <a href="<?= e(url('article', ['slug' => $article['slug']])) ?>" title="<?= e($article['title']) ?>">
                            <strong class="title"><?= e($article['title']) ?></strong>
                        </a>
                        <p><?= e(mb_strimwidth((string) $article['excerpt'], 0, 260, '...')) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($articles === []): ?>
                <div class="col-12"><p class="muted">No articles have been published yet.</p></div>
            <?php endif; ?>
        </div>
        <div class="pagination-control">
            <a class="item page current" href="<?= e(url('articles')) ?>">1</a>
        </div>
    </div>
</div>

<div class="box-online-support support-band">
    <div class="container text-center">
        <div class="default-section-description">
            <h2>Customers Reviews</h2>
            <p>Please leave your review on products or service below.<br> Thank you beforehand.</p>
            <ul>
                <li>Very easy to order</li>
                <li>Easy to navigate. Ordering is easy to understand.</li>
            </ul>
            <a href="<?= e(url('all')) ?>" title="Write a Review" class="btn btn-mix btn-outline-light mr-sm-3 mt-3">Write a Review</a>
            <a href="<?= e(url('articles')) ?>" title="View Reviews" class="btn btn-mix btn-outline-light mt-3">View Reviews</a>
        </div>
    </div>
</div>
