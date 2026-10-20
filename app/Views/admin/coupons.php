<div class="admin-topbar">
    <h1>Coupons</h1>
</div>

<div class="admin-panel">
    <h2>Create a coupon</h2>
    <form method="post" action="<?= e(url('admin/coupon/save')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label for="code">Code</label>
                <input id="code" type="text" name="code" required placeholder="WELCOME10">
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="percent">Percent off</option>
                    <option value="fixed">Fixed amount off</option>
                </select>
            </div>
            <div class="form-group">
                <label for="value">Value</label>
                <input id="value" type="number" step="0.01" min="0.01" name="value" required>
            </div>
            <div class="form-group">
                <label for="min_subtotal">Minimum subtotal</label>
                <input id="min_subtotal" type="number" step="0.01" min="0" name="min_subtotal" value="0">
            </div>
            <div class="form-group">
                <label for="expires_at">Expires on</label>
                <input id="expires_at" type="date" name="expires_at">
            </div>
            <div class="form-group">
                <label for="usage_limit">Usage limit</label>
                <input id="usage_limit" type="number" min="0" name="usage_limit" placeholder="Unlimited">
            </div>
            <div class="form-group">
                <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Create coupon</button>
    </form>
</div>

<div class="admin-panel">
    <h2>Existing coupons</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min. order</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><strong><?= e($coupon['code']) ?></strong></td>
                        <td><?= e($coupon['type']) ?></td>
                        <td><?= $coupon['type'] === 'percent' ? rtrim(rtrim((string) $coupon['value'], '0'), '.') . '%' : money($coupon['value']) ?></td>
                        <td><?= money($coupon['min_subtotal']) ?></td>
                        <td><?= (int) $coupon['used_count'] ?><?= $coupon['usage_limit'] ? ' / ' . (int) $coupon['usage_limit'] : '' ?></td>
                        <td class="small"><?= e($coupon['expires_at'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) $coupon['is_active'] === 1): ?>
                                <span class="badge badge-approved">active</span>
                            <?php else: ?>
                                <span class="badge badge-cancelled">inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="<?= e(url('admin/coupon/delete')) ?>" class="inline-form" onsubmit="return confirm('Delete this coupon?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $coupon['id'] ?>">
                                <button class="btn btn-sm btn-light" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($coupons === []): ?>
                    <tr><td colspan="8" class="muted">No coupons yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
