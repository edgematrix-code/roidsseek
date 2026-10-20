<div class="admin-topbar">
    <h1>Products</h1>
    <div style="display:flex;gap:10px;align-items:center;">
        <form class="admin-search" method="get" action="index.php">
            <input type="hidden" name="r" value="admin/products">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name, SKU, slug">
            <button class="btn btn-light" type="submit">Search</button>
        </form>
        <a class="btn btn-primary" href="<?= e(url('admin/product')) ?>">+ New product</a>
    </div>
</div>

<div class="admin-panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th></th><th>Name</th><th>SKU</th><th>Brand</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><img src="<?= e(image_url($product['image'])) ?>" alt="" width="44" height="44" style="object-fit:contain;"></td>
                        <td>
                            <a href="<?= e(url('admin/product', ['id' => $product['id']])) ?>"><strong><?= e($product['name']) ?></strong></a>
                            <div class="small muted"><?= e($product['slug']) ?></div>
                        </td>
                        <td class="small"><?= e($product['sku']) ?></td>
                        <td class="small"><?= e($product['brand_name'] ?? '—') ?></td>
                        <td><?= money($product['price']) ?></td>
                        <td><?= (int) $product['stock'] ?></td>
                        <td>
                            <?php if ((int) $product['is_active'] === 1): ?>
                                <span class="badge badge-approved">active</span>
                            <?php else: ?>
                                <span class="badge badge-cancelled">hidden</span>
                            <?php endif; ?>
                        </td>
                        <td><a class="small" href="<?= e(url('product', ['slug' => $product['slug']])) ?>" target="_blank" rel="noopener">View</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?>
                    <tr><td colspan="8" class="muted">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager['pages'] > 1): ?>
        <nav class="pagination">
            <?php for ($page = 1; $page <= $pager['pages']; $page++): ?>
                <?php if ($page === $pager['current']): ?>
                    <span class="current"><?= $page ?></span>
                <?php else: ?>
                    <a href="<?= e(url('admin/products', ['q' => $search, 'page' => $page])) ?>"><?= $page ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>
