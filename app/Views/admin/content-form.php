<?php
$isEdit = !empty($row['id']);
$base = ['entity' => $entityKey];
$value = static fn (string $column, $default = '') => e($row[$column] ?? $default);
?>
<div class="admin-topbar">
    <h1><?= $isEdit ? 'Edit' : 'New' ?> <?= e($entity['singular']) ?></h1>
    <a class="btn btn-light" href="<?= e(url('admin/content', $base)) ?>">&larr; Back to <?= e(strtolower($entity['label'])) ?></a>
</div>

<form method="post" action="<?= e(url('admin/content/save', $base)) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">

    <div class="admin-panel">
        <h2><?= e($entity['singular']) ?> details</h2>
        <div class="form-grid">
            <?php foreach ($entity['fields'] as $column => $field): ?>
                <?php $type = $field['type']; ?>
                <div class="form-group<?= $type === 'textarea' ? ' full' : '' ?>">
                    <?php if ($type === 'checkbox'): ?>
                        <label class="checkbox-row">
                            <input type="checkbox" name="<?= e($column) ?>" value="1"
                                <?= $isEdit ? (!empty($row[$column]) ? 'checked' : '') : 'checked' ?>>
                            <?= e($field['label']) ?>
                        </label>
                    <?php else: ?>
                        <label for="field-<?= e($column) ?>"><?= e($field['label']) ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea id="field-<?= e($column) ?>" name="<?= e($column) ?>"
                                      rows="<?= (int) ($field['rows'] ?? 6) ?>"
                                      <?= !empty($field['required']) ? 'required' : '' ?>><?= $value($column) ?></textarea>
                        <?php elseif ($type === 'number'): ?>
                            <input id="field-<?= e($column) ?>" type="number" step="1" name="<?= e($column) ?>"
                                   value="<?= $value($column, '0') ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                        <?php elseif ($type === 'date'): ?>
                            <input id="field-<?= e($column) ?>" type="date" name="<?= e($column) ?>"
                                   value="<?= $value($column) ?>">
                        <?php else: ?>
                            <input id="field-<?= e($column) ?>" type="text" name="<?= e($column) ?>"
                                   value="<?= $value($column) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                        <?php endif; ?>
                        <?php if (!empty($field['hint'])): ?>
                            <div class="form-hint"><?= e($field['hint']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($errors[$column])): ?>
                        <div class="field-error"><?= e($errors[$column]) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex-between">
        <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save changes' : 'Create ' . e($entity['singular']) ?></button>
        <?php if ($isEdit): ?>
            <button type="submit" class="btn btn-danger" form="delete-<?= e($entityKey) ?>">Delete</button>
        <?php endif; ?>
    </div>
</form>

<?php if ($isEdit): ?>
    <form id="delete-<?= e($entityKey) ?>" method="post" action="<?= e(url('admin/content/delete', $base)) ?>"
          onsubmit="return confirm('Delete this <?= e(strtolower($entity['singular'])) ?> permanently?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
    </form>
<?php endif; ?>
