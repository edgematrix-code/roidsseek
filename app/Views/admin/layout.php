<?php
$adminUser = user();
$pendingReviews = (int) \App\Support\Database::value("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
$unreadMessages = (int) \App\Support\Database::value('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
$pendingOrders = (int) \App\Support\Database::value("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$route = $_GET['r'] ?? 'admin';
$contentEntity = (string) ($_GET['entity'] ?? '');
$success = flash('success');
$error = flash('error');
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin - RoidsSeek') ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="admin-body">
<header class="admin-header">
    <div class="container nav-container">
        <button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-sidebar">
            <img src="assets/img/mob-menu.svg" alt="Menu">
            <span>Menu</span>
        </button>
        <a class="logo" href="<?= e(url()) ?>">
            <img src="assets/img/77094.webp" alt="www.roidsseek.to" title="www.roidsseek.to">
        </a>
        <span class="admin-console-tag">Admin console</span>
        <div class="admin-header-user">
            <span class="admin-header-name"><?= e($adminUser['name'] ?? 'Administrator') ?></span>
            <a class="admin-header-link" href="<?= e(url()) ?>">View store</a>
            <a class="admin-header-link admin-header-link--ghost" href="<?= e(url('logout')) ?>">Sign out</a>
        </div>
    </div>
</header>

<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <p class="admin-sidebar-title">Manage</p>
        <nav class="admin-nav">
            <a class="<?= $route === 'admin' ? 'active' : '' ?>" href="<?= e(url('admin')) ?>">Dashboard</a>

            <div class="admin-nav-label">Catalogue</div>
            <a class="<?= str_starts_with($route, 'admin/product') ? 'active' : '' ?>" href="<?= e(url('admin/products')) ?>">Products</a>
            <a class="<?= $route === 'admin/content' && $contentEntity === 'categories' ? 'active' : '' ?>" href="<?= e(url('admin/content', ['entity' => 'categories'])) ?>">Categories</a>
            <a class="<?= $route === 'admin/content' && $contentEntity === 'brands' ? 'active' : '' ?>" href="<?= e(url('admin/content', ['entity' => 'brands'])) ?>">Brands</a>
            <a class="<?= $route === 'admin/content' && $contentEntity === 'goals' ? 'active' : '' ?>" href="<?= e(url('admin/content', ['entity' => 'goals'])) ?>">Goals</a>

            <div class="admin-nav-label">Content</div>
            <a class="<?= $route === 'admin/content' && $contentEntity === 'articles' ? 'active' : '' ?>" href="<?= e(url('admin/content', ['entity' => 'articles'])) ?>">Articles</a>
            <a class="<?= $route === 'admin/content' && $contentEntity === 'events' ? 'active' : '' ?>" href="<?= e(url('admin/content', ['entity' => 'events'])) ?>">Events</a>
            <a class="<?= $route === 'admin/content' && $contentEntity === 'faqs' ? 'active' : '' ?>" href="<?= e(url('admin/content', ['entity' => 'faqs'])) ?>">FAQs</a>
            <a class="<?= $route === 'admin/content' && $contentEntity === 'pages' ? 'active' : '' ?>" href="<?= e(url('admin/content', ['entity' => 'pages'])) ?>">Pages</a>

            <div class="admin-nav-label">Store</div>
            <a class="<?= str_starts_with($route, 'admin/order') ? 'active' : '' ?>" href="<?= e(url('admin/orders')) ?>">
                Orders <?php if ($pendingOrders): ?><span class="admin-nav-badge"><?= $pendingOrders ?></span><?php endif; ?>
            </a>
            <a class="<?= str_starts_with($route, 'admin/coupon') ? 'active' : '' ?>" href="<?= e(url('admin/coupons')) ?>">Coupons</a>
            <a class="<?= str_starts_with($route, 'admin/review') ? 'active' : '' ?>" href="<?= e(url('admin/reviews')) ?>">
                Reviews <?php if ($pendingReviews): ?><span class="admin-nav-badge"><?= $pendingReviews ?></span><?php endif; ?>
            </a>
            <a class="<?= str_starts_with($route, 'admin/message') ? 'active' : '' ?>" href="<?= e(url('admin/messages')) ?>">
                Messages <?php if ($unreadMessages): ?><span class="admin-nav-badge"><?= $unreadMessages ?></span><?php endif; ?>
            </a>

            <div class="admin-nav-label">People</div>
            <a class="<?= $route === 'admin/users' ? 'active' : '' ?>" href="<?= e(url('admin/users')) ?>">Customers</a>

            <div class="admin-nav-label">Site</div>
            <a href="<?= e(url()) ?>" target="_blank" rel="noopener">View store &nearr;</a>
            <a href="<?= e(url('logout')) ?>">Sign out</a>
        </nav>
    </aside>

    <main class="admin-main">
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?= $content ?>
    </main>
</div>

<div class="admin-footer">
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</div>

<script>
    (function () {
        var toggle = document.querySelector('.admin-menu-toggle');
        var sidebar = document.getElementById('admin-sidebar');
        if (!toggle || !sidebar) {
            return;
        }
        toggle.addEventListener('click', function () {
            var open = sidebar.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    })();
</script>
</body>
</html>
