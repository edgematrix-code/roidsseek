<?php
$p = $product ?? [];
$isEdit = !empty($p['id']);
$val = static fn(string $key, $default = '') => e($p[$key] ?? $default);
?>
<div class="admin-topbar">
    <h1><?= $isEdit ? 'Edit product' : 'New product' ?></h1>
    <a class="btn btn-light" href="<?= e(url('admin/products')) ?>">&larr; Back to products</a>
</div>

<form method="post" action="<?= e(url('admin/product/save')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 0) ?>">

    <div class="admin-panel">
        <h2>Basics</h2>
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Name</label>
                <input id="name" type="text" name="name" required value="<?= $val('name') ?>">
                <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input id="slug" type="text" name="slug" value="<?= $val('slug') ?>" placeholder="auto-generated from the name">
                <?php if (!empty($errors['slug'])): ?><div class="field-error"><?= e($errors['slug']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="sku">SKU</label>
                <input id="sku" type="text" name="sku" value="<?= $val('sku') ?>">
                <?php if (!empty($errors['sku'])): ?><div class="field-error"><?= e($errors['sku']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="brand_id">Brand</label>
                <select id="brand_id" name="brand_id">
                    <option value="0">— none —</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand['id'] ?>" <?= (int) ($p['brand_id'] ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>>
                            <?= e($brand['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="substance">Substance</label>
                <input id="substance" type="text" name="substance" value="<?= $val('substance') ?>">
            </div>
            <div class="form-group">
                <label for="pharmaceutical_name">Pharmaceutical name</label>
                <input id="pharmaceutical_name" type="text" name="pharmaceutical_name" value="<?= $val('pharmaceutical_name') ?>">
            </div>
            <div class="form-group">
                <label for="pack">Pack</label>
                <input id="pack" type="text" name="pack" value="<?= $val('pack') ?>" placeholder="100 tabs">
            </div>
            <div class="form-group">
                <label for="strength">Strength</label>
                <input id="strength" type="text" name="strength" value="<?= $val('strength') ?>" placeholder="20 mg/tab">
            </div>
        </div>
    </div>

    <div class="admin-panel">
        <h2>Pricing &amp; inventory</h2>
        <div class="form-grid">
            <div class="form-group">
                <label for="price">Price (USD)</label>
                <input id="price" type="number" step="0.01" min="0" name="price" value="<?= $val('price', '0.00') ?>" required>
            </div>
            <div class="form-group">
                <label for="old_price">Compare-at price</label>
                <input id="old_price" type="number" step="0.01" min="0" name="old_price" value="<?= $val('old_price') ?>">
            </div>
            <div class="form-group">
                <label for="stock">Stock</label>
                <input id="stock" type="number" min="0" name="stock" value="<?= $val('stock', '0') ?>">
            </div>
            <div class="form-group">
                <label for="shipping_location">Ships from</label>
                <select id="shipping_location" name="shipping_location">
                    <?php foreach (['both' => 'USA &amp; International', 'usa' => 'USA domestic', 'international' => 'International'] as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($p['shipping_location'] ?? 'both') === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="image">Image path</label>
                <input id="image" type="text" name="image" value="<?= $val('image') ?>" placeholder="assets/products/example--s600.webp">
                <div class="form-hint">Relative to the site root. Leave blank to use the placeholder.</div>
            </div>
            <div class="form-group">
                <label for="ribbon">Ribbon label</label>
                <input id="ribbon" type="text" name="ribbon" value="<?= $val('ribbon') ?>" placeholder="Shipped USA Domestic">
            </div>
        </div>
    </div>

    <div class="admin-panel">
        <h2>SEO &amp; compliance</h2>
        <p class="form-hint" style="margin-top:0;">
            These fields drive metadata, structured data, the sitemap and the product feed.
            Leave them blank when the value is unknown: nothing is guessed or published.
        </p>
        <div class="form-grid">
            <div class="form-group">
                <label for="meta_title">Meta title override</label>
                <input id="meta_title" type="text" name="meta_title" value="<?= $val('meta_title') ?>" maxlength="190">
            </div>
            <div class="form-group">
                <label for="meta_description">Meta description override</label>
                <input id="meta_description" type="text" name="meta_description" value="<?= $val('meta_description') ?>" maxlength="320">
            </div>
            <div class="form-group">
                <label for="image_alt">Image alt text</label>
                <input id="image_alt" type="text" name="image_alt" value="<?= $val('image_alt') ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label for="rx_status">Rx / OTC status</label>
                <select id="rx_status" name="rx_status">
                    <?php foreach (['unknown' => 'Not classified (noindex-safe defaults)', 'otc' => 'Over the counter', 'rx' => 'Prescription only'] as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($p['rx_status'] ?? 'unknown') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="active_ingredient">Active ingredient</label>
                <input id="active_ingredient" type="text" name="active_ingredient" value="<?= $val('active_ingredient') ?>">
            </div>
            <div class="form-group">
                <label for="dosage_form">Dosage form</label>
                <input id="dosage_form" type="text" name="dosage_form" value="<?= $val('dosage_form') ?>" placeholder="Tablet, vial, capsule">
            </div>
            <div class="form-group">
                <label for="manufacturer">Manufacturer</label>
                <input id="manufacturer" type="text" name="manufacturer" value="<?= $val('manufacturer') ?>">
            </div>
            <div class="form-group">
                <label for="registration_number">Registration number</label>
                <input id="registration_number" type="text" name="registration_number" value="<?= $val('registration_number') ?>">
            </div>
            <div class="form-group">
                <label for="ndc">NDC</label>
                <input id="ndc" type="text" name="ndc" value="<?= $val('ndc') ?>">
            </div>
            <div class="form-group">
                <label for="gtin">GTIN / barcode</label>
                <input id="gtin" type="text" name="gtin" value="<?= $val('gtin') ?>" inputmode="numeric">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-row"><input type="checkbox" name="is_restricted" value="1" <?= !empty($p['is_restricted']) ? 'checked' : '' ?>> Restricted product (noindex, excluded from sitemap, feed and related products)</label>
            <label class="checkbox-row"><input type="checkbox" name="discontinued" value="1" <?= !empty($p['discontinued']) ? 'checked' : '' ?>> Discontinued (301 to the closest equivalent, or 410)</label>
            <label class="checkbox-row"><input type="checkbox" name="feed_eligible" value="1" <?= !empty($p['feed_eligible']) ? 'checked' : '' ?>> Eligible for the Google Merchant feed</label>
            <label class="checkbox-row"><input type="checkbox" name="drug_schema_reviewed" value="1" <?= !empty($p['drug_schema_reviewed']) ? 'checked' : '' ?>> Drug fields reviewed by a pharmacist (adds Drug schema)</label>
        </div>
    </div>

    <div class="admin-panel">
        <h2>Descriptions</h2>
        <div class="form-group">
            <label for="short_description">Short description</label>
            <textarea id="short_description" name="short_description" style="min-height:80px;"><?= $val('short_description') ?></textarea>
        </div>
        <div class="form-group">
            <label for="description">Full description</label>
            <textarea id="description" name="description"><?= $val('description') ?></textarea>
        </div>
    </div>

    <div class="admin-panel">
        <h2>Categorisation</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Categories</label>
                <?php foreach ($categories as $category): ?>
                    <label class="checkbox-row" style="margin-bottom:6px;">
                        <input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>"
                            <?= in_array((int) $category['id'], $selectedCategories, true) ? 'checked' : '' ?>>
                        <?= e($category['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label>Goals</label>
                <?php foreach ($goals as $goal): ?>
                    <label class="checkbox-row" style="margin-bottom:6px;">
                        <input type="checkbox" name="goals[]" value="<?= (int) $goal['id'] ?>"
                            <?= in_array((int) $goal['id'], $selectedGoals, true) ? 'checked' : '' ?>>
                        <?= e($goal['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label class="checkbox-row"><input type="checkbox" name="is_featured" value="1" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> Featured on the home page</label>
                <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?= !isset($p['is_active']) || !empty($p['is_active']) ? 'checked' : '' ?>> Visible in the store</label>
            </div>
        </div>
    </div>

    <div class="flex-between">
        <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
        <?php if ($isEdit): ?>
            <button type="submit" class="btn btn-danger" form="delete-product">Delete</button>
        <?php endif; ?>
    </div>
</form>

<?php if ($isEdit): ?>
    <form id="delete-product" method="post" action="<?= e(url('admin/product/delete')) ?>" onsubmit="return confirm('Delete this product permanently?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
    </form>
<?php endif; ?>
