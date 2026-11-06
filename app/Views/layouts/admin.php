<?php
use App\Support\Nav;
$user = user();
$flashSuccess = flash('success');
$flashError = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/compat.css')) ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar__brand">
            <a class="admin-logo" href="<?= e(url('admin')) ?>">Admin</a>
        </div>
        <nav>
            <ul class="admin-nav-list">
                <li><a href="<?= e(url('admin')) ?>">Dashboard</a></li>
                <li><a href="<?= e(url('admin/products')) ?>">Products</a></li>
                <li><a href="<?= e(url('admin/orders')) ?>">Orders</a></li>
                <li><a href="<?= e(url('admin/coupons')) ?>">Coupons</a></li>
                <li><a href="<?= e(url('admin/reviews')) ?>">Reviews</a></li>
                <li><a href="<?= e(url('admin/messages')) ?>">Messages</a></li>
                <li><a href="<?= e(url('admin/content')) ?>">Content</a></li>
                <li><a href="<?= e(url('admin/users')) ?>">Users</a></li>
            </ul>
        </nav>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-header__top">
                <div class="admin-header__title">
                    <div>
                        <h1><?= e($pageTitle) ?></h1>
                        <?php if ($subtitle): ?>
                            <div class="admin-header__subtitle muting"><?= e($subtitle) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="admin-header__user">
                    <span class="muting small"><?= e($user['name'] ?? '') ?></span>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('logout')) ?>">Logout</a>
                </div>
            </div>
        </header>
        <div class="admin-body">
            <?php if ($flashSuccess): ?>
                <div class="admin-flash admin-flash--success"><?= e($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="admin-flash admin-flash--error"><?= e($flashError) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </main>
</div>
</body>
</html>
<script>
(function () {
    var button = document.querySelector('[data-copy-target]');
    if (!button) { return; }
    button.addEventListener('click', function () {
        var target = document.getElementById(button.getAttribute('data-copy-target'));
        if (!target) { return; }
        var text = target.textContent.trim();
        var done = function () {
            var original = button.textContent;
            button.textContent = 'Copied';
            setTimeout(function () { button.textContent = original; }, 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, done);
            return;
        }
        var range = document.createRange();
        range.selectNodeContents(target);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        try { document.execCommand('copy'); } catch (error) {}
        done();
    });
})();
</script>
