<?php $currentId = (int) (user()['id'] ?? 0); ?>
<div class="admin-topbar">
    <h1>Customers</h1>
</div>

<div class="admin-panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Orders</th><th>Lifetime value</th><th>Joined</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <td>
                            <strong><?= e($account['name']) ?></strong>
                            <?php if ((int) $account['id'] === $currentId): ?><div class="small muted">you</div><?php endif; ?>
                        </td>
                        <td class="small"><?= e($account['email']) ?></td>
                        <td class="small"><?= e($account['phone'] ?? '—') ?></td>
                        <td><span class="badge badge-<?= $account['role'] === 'admin' ? 'paid' : 'pending' ?>"><?= e($account['role']) ?></span></td>
                        <td><?= (int) $account['order_count'] ?></td>
                        <td><?= money($account['lifetime_value']) ?></td>
                        <td class="small"><?= e(date('M j, Y', strtotime($account['created_at']))) ?></td>
                        <td class="text-right" style="white-space:nowrap;">
                            <?php if ((int) $account['id'] !== $currentId): ?>
                                <form class="inline-form" method="post" action="<?= e(url('admin/user/role')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                    <input type="hidden" name="role" value="<?= $account['role'] === 'admin' ? 'customer' : 'admin' ?>">
                                    <button class="btn btn-sm btn-light" type="submit">
                                        <?= $account['role'] === 'admin' ? 'Make customer' : 'Make admin' ?>
                                    </button>
                                </form>
                                <form class="inline-form" method="post" action="<?= e(url('admin/user/delete')) ?>"
                                      onsubmit="return confirm('Delete this account permanently? Their orders are kept.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
