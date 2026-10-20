<?php
use App\Support\Nav;

$allCategories = Nav::categories();

$query = array_merge($routeParams, [
    'brand' => $filters['brand'],
    'substance' => $filters['substance'],
]);
if ($filters['location'] !== null) {
    $query['location'] = $filters['location'];
}
if ($filters['max_price'] !== null) {
    $query['max_price'] = $filters['max_price'];
}
if ($filters['stock'] !== null) {
    $query['stock'] = $filters['stock'];
}
if ($filters['sort'] !== 'featured') {
    $query['sort'] = $filters['sort'];
}

$hasFilters = $filters['brand'] !== [] || $filters['substance'] !== [] || $filters['location'] !== null
    || $filters['max_price'] !== null || $filters['stock'] !== null;
?>
<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span><?= e($heading) ?></span></li>
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
                <h1 class="h1 category-title"><?= e($heading) ?> <small>(<?= (int) $pager['total'] ?> Offers)</small></h1>
            </div>
        </div>
    </div>
</div>

<?php if ($description !== ''): ?>
    <div class="container">
        <div class="row no-gutters justify-content-end">
            <div class="col-lg-10 listing-intro mt-5 mb-4">
                <p><?= nl2br(e($description)) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="filter-bar">
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="row justify-content-between align-items-center">
                    <div class="col-12 col-lg-2">
                        <a class="btn btn-sm btn-outline-primary open-filter-btn" href="#">Filters</a>
                    </div>
                    <div class="col-12 col-lg-10">
                        <div class="row">
                            <div class="col-4 d-flex align-items-center">
                                <span class="toolbar-note">
                                    <?= (int) $pager['total'] ?> products
                                    <?php if ($pager['total'] > 0): ?>
                                        &middot; showing <?= (int) $pager['from'] ?>-<?= (int) $pager['to'] ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="col-8 d-flex align-items-center justify-content-end flex-nowrap text-nowrap text-right">
                                <div class="box-sort filter-group">
                                    Sorted By:
                                    <form method="get" action="index.php">
                                        <input type="hidden" name="r" value="<?= e($route) ?>">
                                        <?php foreach ($routeParams as $key => $value): ?>
                                            <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                                        <?php endforeach; ?>
                                        <?php foreach ($filters['brand'] as $brandId): ?>
                                            <input type="hidden" name="brand[]" value="<?= (int) $brandId ?>">
                                        <?php endforeach; ?>
                                        <?php foreach ($filters['substance'] as $substance): ?>
                                            <input type="hidden" name="substance[]" value="<?= e($substance) ?>">
                                        <?php endforeach; ?>
                                        <?php if ($filters['location'] !== null): ?>
                                            <input type="hidden" name="location" value="<?= e($filters['location']) ?>">
                                        <?php endif; ?>
                                        <?php if ($filters['max_price'] !== null): ?>
                                            <input type="hidden" name="max_price" value="<?= e((string) $filters['max_price']) ?>">
                                        <?php endif; ?>
                                        <div class="select-custom-mini">
                                            <select class="sort-options" name="sort" onchange="this.form.submit()">
                                                <option value="featured" <?= $filters['sort'] === 'featured' ? 'selected' : '' ?>>Default</option>
                                                <option value="name_asc" <?= $filters['sort'] === 'name_asc' ? 'selected' : '' ?>>Title</option>
                                                <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                                                <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                                                <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-12 col-md-2 category-menu">
            <h4>Category Filter <span class="close-filter-btn">
                <svg width="15" height="15" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g fill="#444444">
                            <polygon points="0 0.7 6.8 7.5 0 14.3 0.7 15 7.5 8.2 14.3 15 15 14.3 0.7 0"></polygon>
                        </g>
                    </g>
                </svg>
            </span></h4>
            <ul class="category-top-menu">
                <li class="filter-group js-category active">
                    <span class="collapse-icons facet-title collapse-opened">Categories</span>
                    <ul class="category-sub-menu categories-list" style="display:block;">
                        <li>
                            <a class="link <?= $type === 'all' && $route === 'products' ? 'active' : '' ?>" href="<?= e(url('all')) ?>">
                                <span>All Products</span><span class="count"><?= array_sum(array_map(static fn ($c) => (int) $c['product_count'], $allCategories)) ?></span>
                            </a>
                        </li>
                        <?php foreach ($allCategories as $category): ?>
                            <li>
                                <a class="link <?= ($route === 'category' && ($routeParams['slug'] ?? '') === $category['slug']) ? 'active' : '' ?>"
                                   href="<?= e(url('category', ['slug' => $category['slug']])) ?>">
                                    <span><?= e($category['name']) ?></span><span class="count"><?= (int) $category['product_count'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <li class="filter-group js-manufacturers">
                    <span class="collapse-icons facet-title">Manufacturers</span>
                    <form class="js-auto-filter" method="get" action="index.php">
                        <input type="hidden" name="r" value="<?= e($route) ?>">
                        <?php foreach ($routeParams as $key => $value): ?>
                            <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
                        <?php foreach ($filters['substance'] as $substance): ?>
                            <input type="hidden" name="substance[]" value="<?= e($substance) ?>">
                        <?php endforeach; ?>
                        <ul class="category-sub-menu" data-array="manufacturers" style="display:none;">
                            <?php foreach ($brands as $brand): ?>
                                <li>
                                    <label class="link">
                                        <span>
                                            <input type="checkbox" name="brand[]" value="<?= (int) $brand['id'] ?>"
                                                <?= in_array((int) $brand['id'], $filters['brand'], true) ? 'checked' : '' ?>>
                                            <?= e($brand['name']) ?>
                                        </span>
                                        <span class="count">(<?= (int) $brand['total'] ?>)</span>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input class="form-control" type="number" name="max_price" min="0" step="5" placeholder="Max price"
                               value="<?= $filters['max_price'] !== null ? e((string) $filters['max_price']) : '' ?>" style="height:38px;font-size:12px;margin-top:10px;">
                        <?php if ($filters['location'] !== null): ?>
                            <input type="hidden" name="location" value="<?= e($filters['location']) ?>">
                        <?php endif; ?>
                    </form>
                </li>

                <?php if ($substances !== []): ?>
                    <li class="filter-group js-brands">
                        <span class="collapse-icons facet-title">Substance</span>
                        <form class="js-auto-filter" method="get" action="index.php">
                            <input type="hidden" name="r" value="<?= e($route) ?>">
                            <?php foreach ($routeParams as $key => $value): ?>
                                <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                            <?php endforeach; ?>
                            <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
                            <?php foreach ($filters['brand'] as $brandId): ?>
                                <input type="hidden" name="brand[]" value="<?= (int) $brandId ?>">
                            <?php endforeach; ?>
                            <?php if ($filters['max_price'] !== null): ?>
                                <input type="hidden" name="max_price" value="<?= e((string) $filters['max_price']) ?>">
                            <?php endif; ?>
                            <ul class="category-sub-menu" data-array="substances" style="display:none;">
                                <?php foreach ($substances as $substance): ?>
                                    <li>
                                        <label class="link">
                                            <span>
                                                <input type="checkbox" name="substance[]" value="<?= e($substance['substance']) ?>"
                                                    <?= in_array($substance['substance'], $filters['substance'], true) ? 'checked' : '' ?>>
                                                <?= e($substance['substance']) ?>
                                            </span>
                                            <span class="count">(<?= (int) $substance['total'] ?>)</span>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </form>
                    </li>
                <?php endif; ?>

                <li class="filter-group js-shipping">
                    <span class="collapse-icons facet-title">Shipping Location</span>
                    <form class="js-auto-filter" method="get" action="index.php">
                        <input type="hidden" name="r" value="<?= e($route) ?>">
                        <?php foreach ($routeParams as $key => $value): ?>
                            <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
                        <?php foreach ($filters['brand'] as $brandId): ?>
                            <input type="hidden" name="brand[]" value="<?= (int) $brandId ?>">
                        <?php endforeach; ?>
                        <?php foreach ($filters['substance'] as $substance): ?>
                            <input type="hidden" name="substance[]" value="<?= e($substance) ?>">
                        <?php endforeach; ?>
                        <ul class="category-sub-menu" data-array="shipping" style="display:none;">
                            <li>
                                <label class="link">
                                    <span><input type="radio" name="location" value="" <?= $filters['location'] === null ? 'checked' : '' ?>> Any location</span>
                                </label>
                            </li>
                            <li>
                                <label class="link">
                                    <span><input type="radio" name="location" value="usa" <?= $filters['location'] === 'usa' ? 'checked' : '' ?>> U.S Domestic</span>
                                </label>
                            </li>
                            <li>
                                <label class="link">
                                    <span><input type="radio" name="location" value="international" <?= $filters['location'] === 'international' ? 'checked' : '' ?>> International</span>
                                </label>
                            </li>
                        </ul>
                    </form>
                </li>

                <?php if ($hasFilters): ?>
                    <a class="btn btn-sm btn-light btn-block filter-apply" href="<?= e(url($route, $routeParams)) ?>">Reset filters</a>
                <?php endif; ?>
            </ul>
        </div>

        <div class="col-12 col-lg-10 col-md-12 category-content">
            <?php if ($products === []): ?>
                <div class="no-results">
                    <h2 class="h1">No products found</h2>
                    <p class="muted">Try removing a filter or using a different search term.</p>
                    <a class="btn btn-mix btn-primary" href="<?= e(url($route, $routeParams)) ?>">Reset filters</a>
                </div>
            <?php else: ?>
                <div id="grid" class="row">
                    <?php foreach ($products as $product): ?>
                        <?= App\Support\View::partial('partials/product-card', ['product' => $product]) ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($pager['pages'] > 1): ?>
                    <div class="pagination-control">
                        <?php if ($pager['current'] > 1): ?>
                            <a class="item" href="<?= e(url($route, $query + ['page' => $pager['current'] - 1])) ?>">&lsaquo;</a>
                        <?php else: ?>
                            <span class="item disabled">&lsaquo;</span>
                        <?php endif; ?>
                        <?php for ($page = 1; $page <= $pager['pages']; $page++): ?>
                            <?php if ($page === $pager['current']): ?>
                                <span class="item page current"><?= $page ?></span>
                            <?php elseif ($page === 1 || $page === $pager['pages'] || abs($page - $pager['current']) <= 2): ?>
                                <a class="item page" href="<?= e(url($route, $query + ['page' => $page])) ?>"><?= $page ?></a>
                            <?php elseif (abs($page - $pager['current']) === 3): ?>
                                <span class="item">&hellip;</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($pager['current'] < $pager['pages']): ?>
                            <a class="item next" href="<?= e(url($route, $query + ['page' => $pager['current'] + 1])) ?>">&rsaquo;</a>
                        <?php else: ?>
                            <span class="item disabled">&rsaquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<div style="height:40px;"></div>
