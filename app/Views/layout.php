<?php
use App\Support\Nav;

$navCategories = Nav::categories();
$navBrands = Nav::brands();
$navGoals = Nav::goals();
$user = user();
$flashSuccess = flash('success');
$flashError = flash('error');
$isHome = ($_GET['r'] ?? '') === '';
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= e($title ?? 'RoidsSeek') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="icon" href="assets/img/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="assets/img/favicon-32.png" sizes="32x32">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <meta name="distribution" content="global">
    <meta name="classification" content="Shopping/Health/Fitness/Medicine/e-Commerce">
    <meta name="keywords" content="buy steroids online, steroids for sale, steroids online, anabolic steroids for sale, buy steroids">
    <meta name="description" content="<?= e($description ?? 'Buy steroids online at RoidsSeek. We offer high-quality anabolic steroids for sale. Buy steroids online at reasonable prices. Fast and Safe delivery.') ?>">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="en_US">
    <meta property="og:site_name" content="WWW.ROIDSSEEK.TO">
    <meta property="og:title" content="<?= e($title ?? 'RoidsSeek') ?>">
    <meta property="og:description" content="<?= e($description ?? 'Buy steroids online at RoidsSeek. We offer high-quality anabolic steroids for sale. Fast and Safe delivery.') ?>">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/compat.css">
</head>
<body class="<?= $isHome ? 'home' : '' ?>">
<div id="quick-search" data-characters="More then 4 characters" data-outofstock="Out of stock" data-search="Search" data-noitems="No Items" data-itemsfound="0 Items Found">
    <div class="quick-search-overlay"></div>
    <div class="quick-search-content">
        <div class="quick-search-field-container container">
            <form action="index.php" method="get" id="searchform">
                <input type="hidden" name="r" value="search">
                <button type="submit">
                    <img src="assets/img/ico-search-444.svg" alt="Search Button" title="Search Button">
                </button>
                <span class="loader">
                    <img src="assets/img/ajax-loader.gif" alt="Ajax Loader">
                </span>
                <input type="text" autocomplete="off" id="textbox" class="text quick-search-field" maxlength="100" name="q" value="" placeholder="Search">
            </form>
            <div id="result"></div>
        </div>
    </div>
</div>

<header class="header-fixed">
    <div class="container nav-container">
        <span class="mob-menu close-btn-js">
            <img src="assets/img/mob-menu.svg" alt="Mobile Menu" title="Mobile Menu">
        </span>
        <a class="logo" href="<?= e(url()) ?>">
            <img src="assets/img/77094.webp" alt="www.roidsseek.to" title="www.roidsseek.to">
        </a>
        <nav class="nav-menu">
            <span class="close-btn close-btn-js">
                <img src="assets/img/ico-close-444.svg" alt="Close Button">
            </span>
            <form action="index.php" method="get" class="searchform-mob" id="searchform-mob">
                <input type="hidden" name="r" value="search">
                <button type="submit">
                    <img src="assets/img/ico-search-444.svg" alt="Search Button">
                </button>
                <input type="text" class="text" maxlength="100" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search">
            </form>
            <ul class="menu-lvl-1">
                <li class="d-lg-none">
                    <a href="<?= e($user ? url('account') : url('login')) ?>">
                        <?= $user ? 'My Account' : 'Login' ?>
                    </a>
                </li>
                <li class="d-lg-none">
                    <a href="<?= e($user ? url('logout') : url('register')) ?>">
                        <?= $user ? 'Logout' : 'Join' ?>
                    </a>
                </li>
                <li class="">
                    <a href="<?= e(url('sale')) ?>"><span style="font-weight: bold;color: red;">ON SALE</span></a>
                </li>
                <li>
                    <span class="has-children">SHIPPING</span>
                    <div class="dropdown-menu">
                        <div class="container">
                            <ul class="menu-lvl-2">
                                <li class="">
                                    <a href="<?= e(url('shipping', ['slug' => 'usa'])) ?>" title="View products in USA DOMESTIC">USA DOMESTIC</a>
                                </li>
                                <li class="">
                                    <a href="<?= e(url('shipping', ['slug' => 'international'])) ?>" title="View products in International">International</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </li>
                <li>
                    <span class="has-children">Categories</span>
                    <div class="dropdown-menu">
                        <div class="container">
                            <ul class="menu-lvl-2">
                                <?php foreach ($navCategories as $category): ?>
                                    <li class="">
                                        <a href="<?= e(url('category', ['slug' => $category['slug']])) ?>" title="View products in <?= e($category['name']) ?>">
                                            <?= e($category['name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </li>
                <li>
                    <span class="has-children">Brands</span>
                    <div class="dropdown-menu">
                        <div class="container">
                            <ul class="menu-lvl-2">
                                <?php foreach ($navBrands as $brand): ?>
                                    <li class="">
                                        <a href="<?= e(url('brand', ['slug' => $brand['slug']])) ?>" title="View products in <?= e($brand['name']) ?>">
                                            <?= e($brand['name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </li>
                <li>
                    <span class="has-children">By Goal</span>
                    <div class="dropdown-menu">
                        <div class="container">
                            <ul class="menu-lvl-2">
                                <?php foreach ($navGoals as $goal): ?>
                                    <li class="">
                                        <a href="<?= e(url('goal', ['slug' => $goal['slug']])) ?>" title="View products in <?= e($goal['name']) ?>">
                                            <?= e($goal['name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </li>
                <li class="">
                    <a href="<?= e(url('events')) ?>">Events</a>
                </li>
                <li class="">
                    <a href="<?= e(url('articles')) ?>">Articles</a>
                </li>
                <li class="">
                    <a href="<?= e(url('faq')) ?>">Faq</a>
                </li>
                <li class="">
                    <a href="<?= e(url('contact')) ?>">Contact Us</a>
                </li>
            </ul>
        </nav>
        <div class="user-info">
            <img id="searchBtn" class="search-btn" src="assets/img/ico-search-444.svg" alt="Search Button" title="Search Button">
            <?php if ($user): ?>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a class="btn std-button" href="<?= e(url('admin')) ?>">Admin</a>
                <?php endif; ?>
                <a class="btn std-button" href="<?= e(url('account')) ?>">My Account</a>
                <a class="btn std-button gray-invers" href="<?= e(url('logout')) ?>">Logout</a>
            <?php else: ?>
                <a class="btn std-button" href="<?= e(url('login')) ?>">Login</a>
                <a class="btn std-button gray-invers" href="<?= e(url('register')) ?>">Join</a>
            <?php endif; ?>
            <div class="cart-container">
                <a class="x-cartmini" href="<?= e(url('cart')) ?>" id="shopping-cart-headr" data-count="<?= cart_count() ?>">
                    <i class="shopping-cart">
                        <svg width="16" height="21" viewBox="0 0 16 21" version="1.1" xmlns="http://www.w3.org/2000/svg">
                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <g fill="#FFF" fill-rule="nonzero">
                                    <path d="M15.5,5.3 L12,5.3 L12,4.4 C12,2.1 10.2,0.2 8,0.2 C5.8,0.2 4,2.1 4,4.4 L4,5.3 L0.5,5.3 C0.2,5.3 0,5.5 0,5.8 L0,20.4 C0,20.7 0.2,20.9 0.5,20.9 L15.5,20.9 C15.8,20.9 16,20.7 16,20.4 L16,5.8 C16,5.5 15.8,5.3 15.5,5.3 Z M5,4.4 C5,2.7 6.4,1.2 8.1,1.2 C9.7,1.2 11,2.7 11,4.4 L11,5.3 L5,5.3 L5,4.4 Z M15,19.8 L1,19.8 L1,6.3 L4,6.3 L4,7.8 C4,8 4.2,8.2 4.5,8.2 C4.8,8.2 5,8 5,7.7 L5,6.3 L11,6.3 L11,7.8 C11,8.1 11.2,8.3 11.5,8.3 C11.8,8.3 12,8 12,7.7 L12,6.3 L15,6.3 L15,19.8 Z"></path>
                                </g>
                            </g>
                        </svg>
                    </i>
                    <span class="info">Cart (</span><span class="items"><?= cart_count() ?></span><span class="info">)</span>
                </a>
            </div>
        </div>
    </div>
</header>

<section class="wrapp">
    <main class="main">
        <?php if ($flashSuccess): ?>
            <div class="container">
                <div class="site-flash site-flash--success"><?= e($flashSuccess) ?></div>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="container">
                <div class="site-flash site-flash--error"><?= e($flashError) ?></div>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>
    <?php require __DIR__ . '/partials/footer.php'; ?>
</section>
<script src="assets/js/ui.js"></script>
</body>
</html>
