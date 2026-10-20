<?php $base = ['entity' => $entityKey]; ?>
<div class="admin-topbar">
    <h1><?= e($entity['label']) ?></h1>
    <div class="admin-search">
        <form class="admin-search" method="get" action="index.php">
            <input type="hidden" name="r" value="admin/content">
            <input type="hidden" name="entity" value="<?= e($entityKey) ?>">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($entity['label'])) ?>">
            <button class="btn btn-light" type="submit">Search</button>
        </form>
        <a class="btn btn-primary" href="<?= e(url('admin/content/edit', $base)) ?>">+ New <?= e($entity['singular']) ?></a>
    </div>
</div>

<div class="admin-panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <?php foreach ($entity['columns'] as $column => $meta): ?>
                        <th><?= e($meta['label']) ?></th>
                    <?php endforeach; ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="small muted"><?= (int) $row['id'] ?></td>
                        <?php foreach ($entity['columns'] as $column => $meta): ?>
                            <td>
                                <?php if (($meta['type'] ?? '') === 'boolean'): ?>
                                    <?php if (!empty($row[$column])): ?>
                                        <span class="badge badge-approved">published</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelled">hidden</span>
                                    <?php endif; ?>
                                <?php elseif (($meta['type'] ?? '') === 'excerpt'): ?>
                                    <span class="small muted"><?= e(mb_strimwidth((string) ($row[$column] ?? ''), 0, 90, '...')) ?></span>
                                <?php elseif ($column === 'title' || $column === 'name' || $column === 'question'): ?>
                                    <a href="<?= e(url('admin/content/edit', $base + ['id' => $row['id']])) ?>"><strong><?= e($row[$column]) ?></strong></a>
                                <?php else: ?>
                                    <?= e((string) ($row[$column] ?? '')) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-right" style="white-space:nowrap;">
                            <a class="btn btn-sm btn-light" href="<?= e(url('admin/content/edit', $base + ['id' => $row['id']])) ?>">Edit</a>
                            <form class="inline-form" method="post" action="<?= e(url('admin/content/delete', $base)) ?>"
                                  onsubmit="return confirm('Delete this <?= e(strtolower($entity['singular'])) ?> permanently?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= count($entity['columns']) + 2 ?>" class="muted">No <?= e(strtolower($entity['label'])) ?> found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
