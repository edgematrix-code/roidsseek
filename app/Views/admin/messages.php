<div class="admin-topbar">
    <h1>Contact messages</h1>
</div>

<?php if ($messages === []): ?>
    <div class="admin-panel"><p class="muted">No messages yet.</p></div>
<?php endif; ?>

<?php foreach ($messages as $message): ?>
    <div class="admin-panel">
        <div class="flex-between">
            <div>
                <h2 class="mb-0">
                    <?= e($message['subject'] ?: 'No subject') ?>
                    <?php if (!(int) $message['is_read']): ?><span class="admin-nav-badge">new</span><?php endif; ?>
                </h2>
                <div class="small muted">
                    <?= e($message['name']) ?> &lt;<?= e($message['email']) ?>&gt; &middot;
                    <?= e(date('M j, Y H:i', strtotime($message['created_at']))) ?>
                </div>
            </div>
            <form method="post" action="<?= e(url('admin/message/delete')) ?>" class="inline-form" onsubmit="return confirm('Delete this message?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                <button class="btn btn-sm btn-light" type="submit">Delete</button>
            </form>
        </div>
        <p class="mt-2"><?= nl2br(e($message['message'])) ?></p>
        <a class="btn btn-sm btn-outline-primary" href="mailto:<?= e($message['email']) ?>">Reply by email</a>
    </div>
<?php endforeach; ?>
