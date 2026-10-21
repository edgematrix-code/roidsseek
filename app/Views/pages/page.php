<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span><?= e($page['title']) ?></span></li>
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
                <h1 class="h1"><?= e($page['title']) ?></h1>
                <div class="info-meta">
                    <div class="reading-time">Reading Time: 2 min</div>
                </div>
                <div class="info-content">
                    <?= $page['content'] ?>
                </div>
                <?php if (in_array($page['slug'], ['licence', 'about', 'contact', 'prescription-policy'], true)): ?>
                    <?= \App\Support\View::partial('partials/regulatory') ?>
                <?php endif; ?>
                <?php if (in_array($page['slug'], ['licence', 'medical-disclaimer', 'prescription-policy', 'editorial-policy', 'privacy-policy', 'terms-of-use', 'returns'], true)): ?>
                    <p class="small muted mt-3">
                        This page is a template for the store owner and requires professional legal review before launch.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
