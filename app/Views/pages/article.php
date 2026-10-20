<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><a href="<?= e(url('articles')) ?>"><span>Articles</span></a></li>
                        <li><span><?= e($article['title']) ?></span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data info-page-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="h1"><?= e($article['title']) ?></h1>
                <div class="info-meta">
                    <?php if (!empty($article['published_at'])): ?>
                        <?= e(date('F j, Y', strtotime($article['published_at']))) ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($article['image'])): ?>
                    <img src="<?= e(image_url($article['image'])) ?>" alt="<?= e($article['title']) ?>" class="img-fluid" style="display:block;width:100%;height:auto;border-radius:8px;margin-bottom:24px;">
                <?php endif; ?>
                <div class="info-content">
                    <p><?= nl2br(e($article['body'] ?: $article['excerpt'])) ?></p>
                </div>
                <p class="mt-2"><a href="<?= e(url('articles')) ?>">&larr; All articles</a></p>
            </div>
        </div>
    </div>
</div>

<?php if ($more !== []): ?>
    <div class="box-data page-story">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2 class="h1">Keep reading</h2>
                </div>
                <?php foreach ($more as $item): ?>
                    <div class="item col-md-6">
                        <a href="<?= e(url('article', ['slug' => $item['slug']])) ?>" title="<?= e($item['title']) ?>">
                            <img src="<?= e(image_url($item['image'] ?? null)) ?>" alt="<?= e($item['title']) ?>" title="<?= e($item['title']) ?>" class="img-fluid">
                        </a>
                        <div class="news-desc">
                            <div class="info">
                                <span class="date"><?= e($item['published_at'] ? date('M j, Y', strtotime($item['published_at'])) : '') ?></span>
                            </div>
                            <a href="<?= e(url('article', ['slug' => $item['slug']])) ?>" title="<?= e($item['title']) ?>">
                                <strong class="title"><?= e($item['title']) ?></strong>
                            </a>
                            <p><?= e(mb_strimwidth((string) $item['excerpt'], 0, 200, '...')) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
