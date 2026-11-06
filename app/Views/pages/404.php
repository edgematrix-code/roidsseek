<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Page not found</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data">
    <div class="container">
        <div class="empty-state">
            <h2 class="h1">Page not found</h2>
            <p>The page you are looking for has moved or no longer exists.</p>
            <form action="<?= e(url('search')) ?>" method="get" class="page-404-search">
                <?php if ((\App\Support\Site::get('cleanUrls') ?: false) === false): ?>
                    <input type="hidden" name="<?= e(route_param()) ?>" value="search">
                <?php endif; ?>
                <input type="text" name="q" placeholder="Search products" aria-label="Search products">
                <button type="submit" class="btn btn-mix btn-primary">Search</button>
            </form>
            <a class="btn btn-mix btn-primary" href="<?= e(url()) ?>">Back to the store</a>
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Browse products</a>
            <?php if (!empty($popular)): ?>
                <p class="mt-3">
                    Popular categories:
                    <?php foreach ($popular as $index => $category): ?>
                        <?= $index > 0 ? ' &middot; ' : '' ?><a href="<?= e(url('category', ['slug' => $category['slug']])) ?>"><?= e($category['name']) ?></a>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
