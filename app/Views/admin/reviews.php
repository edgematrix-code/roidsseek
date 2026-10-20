<div class="admin-topbar">
    <h1>Reviews</h1>
</div>

<?php if ($reviews === []): ?>
    <div class="admin-panel"><p class="muted">No reviews have been submitted yet.</p></div>
<?php endif; ?>

<?php foreach ($reviews as $review): ?>
    <div class="admin-panel">
        <div class="flex-between">
            <div>
                <h2 class="mb-0"><?= e($review['title'] ?: 'Review') ?></h2>
                <div class="small muted">
                    <a href="<?= e(url('product', ['slug' => $review['product_slug']])) ?>" target="_blank" rel="noopener"><?= e($review['product_name']) ?></a>
                    &middot; <?= e($review['name']) ?> &middot; <?= (int) $review['rating'] ?>/5 &middot;
                    <?= e(date('M j, Y', strtotime($review['created_at']))) ?>
                </div>
            </div>
            <span class="badge badge-<?= e($review['status']) ?>"><?= e($review['status']) ?></span>
        </div>
        <p class="mt-2"><?= nl2br(e($review['body'])) ?></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach (['approved' => 'Approve', 'rejected' => 'Reject', 'pending' => 'Mark pending'] as $status => $label): ?>
                <?php if ($review['status'] !== $status): ?>
                    <form method="post" action="<?= e(url('admin/review/status')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
                        <input type="hidden" name="status" value="<?= e($status) ?>">
                        <button class="btn btn-sm <?= $status === 'approved' ? 'btn-primary' : 'btn-light' ?>" type="submit"><?= e($label) ?></button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
            <form method="post" action="<?= e(url('admin/review/delete')) ?>" class="inline-form" onsubmit="return confirm('Delete this review?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
