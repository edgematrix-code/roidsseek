<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>My Account</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-cat-select">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="category-title">My Account</h1>
            </div>
        </div>
    </div>
</div>

<div class="box-data">
    <div class="container">
        <div class="account-layout">
            <nav class="account-nav">
                <a class="<?= ($section ?? '') === 'profile' ? 'active' : '' ?>" href="<?= e(url('account')) ?>">Profile</a>
                <a href="<?= e(url('account/orders')) ?>">Orders</a>
                <a class="<?= ($section ?? '') === 'password' ? 'active' : '' ?>" href="#password">Password</a>
                <a href="<?= e(url('logout')) ?>">Sign out</a>
            </nav>

            <div class="account-main">
                <div class="account-panel">
                    <h1>Profile details</h1>
                    <p class="muted small mt-0" style="margin-top:-8px;">Signed in as <?= e($user['email']) ?>.</p>
                    <form action="<?= e(url('account/profile')) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full name</label>
                                <input class="form-control" id="name" type="text" name="name" value="<?= e($user['name']) ?>" required>
                                <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input class="form-control" id="email" type="email" name="email" value="<?= e($user['email']) ?>" required>
                                <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input class="form-control" id="phone" type="text" name="phone" value="<?= e($user['phone']) ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-mix btn-primary">Save changes</button>
                    </form>
                </div>

                <div class="account-panel">
                    <h2 class="mt-0">Recent orders</h2>
                    <?php if ($orders === []): ?>
                        <p class="muted">You have not placed an order yet. <a href="<?= e(url('all')) ?>">Start shopping</a>.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?= e($order['order_number']) ?></td>
                                            <td><?= e(date('M j, Y', strtotime($order['created_at']))) ?></td>
                                            <td><?= money($order['total']) ?></td>
                                            <td><span class="badge badge-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                                            <td><a href="<?= e(url('account/order', ['number' => $order['order_number']])) ?>">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="account-panel" id="password">
                    <h2 class="mt-0">Change password</h2>
                    <form action="<?= e(url('account/password')) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="current_password">Current password</label>
                                <input class="form-control" id="current_password" type="password" name="current_password" required>
                                <?php if (!empty($errors['current_password'])): ?><div class="field-error"><?= e($errors['current_password']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New password</label>
                                <input class="form-control" id="new_password" type="password" name="password" required minlength="8">
                                <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation">Confirm new password</label>
                                <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
                                <?php if (!empty($errors['password_confirmation'])): ?><div class="field-error"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-mix btn-primary">Update password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
