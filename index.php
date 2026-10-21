<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Controllers\AccountController;
use App\Controllers\AdminContentController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\SeoController;
use App\Controllers\StoreController;

enforce_canonical_request();
normalize_request_path();
redirect_legacy_url();

[$route, $params] = route_request();
foreach ($params as $key => $value) {
    $_GET[$key] = $value;
}
$method = request_method();
$store = new StoreController();

try {
    switch ($route) {
        // ---------------------------------------------------------- storefront
        case '':
            $store->home();
            break;

        case 'all':
            $store->listing('all');
            break;

        case 'search':
            $store->listing('search');
            break;

        case 'sale':
            $store->listing('sale');
            break;

        case 'category':
        case 'brand':
        case 'goal':
        case 'shipping':
            $store->listing($route, (string) ($_GET['slug'] ?? ''));
            break;

        case 'product':
            $store->product((string) ($_GET['slug'] ?? ''));
            break;

        case 'review':
            $store->review((string) ($_GET['slug'] ?? ''));
            break;

        // ---------------------------------------------------------------- cart
        case 'cart':
            (new CartController())->index();
            break;

        case 'cart/add':
            (new CartController())->add();
            break;

        case 'cart/update':
            (new CartController())->update();
            break;

        case 'cart/remove':
            (new CartController())->remove();
            break;

        case 'cart/clear':
            (new CartController())->clear();
            break;

        case 'cart/coupon':
            (new CartController())->coupon();
            break;

        // ------------------------------------------------------------ checkout
        case 'checkout':
            $checkout = new CheckoutController();
            $method === 'POST' ? $checkout->place() : $checkout->index();
            break;

        case 'order':
            (new CheckoutController())->confirmation((string) ($_GET['number'] ?? ''));
            break;

        // ---------------------------------------------------------------- auth
        case 'login':
            (new AuthController())->login();
            break;

        case 'register':
            (new AuthController())->register();
            break;

        case 'logout':
            (new AuthController())->logout();
            break;

        case 'forgot':
            (new AuthController())->forgot();
            break;

        case 'reset':
            (new AuthController())->reset();
            break;

        // ------------------------------------------------------------- account
        case 'account':
            (new AccountController())->index();
            break;

        case 'account/orders':
            (new AccountController())->orders();
            break;

        case 'account/order':
            (new AccountController())->order((string) ($_GET['number'] ?? ''));
            break;

        case 'account/profile':
            (new AccountController())->updateProfile();
            break;

        case 'account/password':
            (new AccountController())->changePassword();
            break;

        // ------------------------------------------------------------ content
        case 'faq':
            $store->faq();
            break;

        case 'articles':
            $store->articles();
            break;

        case 'article':
            $store->article((string) ($_GET['slug'] ?? ''));
            break;

        case 'events':
            $store->events();
            break;

        case 'page':
            $store->page((string) ($_GET['slug'] ?? ''));
            break;

        case 'contact':
            $store->contact();
            break;

        case 'location':
            $store->location();
            break;

        case 'newsletter':
            $store->newsletter();
            break;

        // -------------------------------------------------------- crawl files
        case 'robots':
            (new SeoController())->robots();
            break;

        case 'sitemap':
            (new SeoController())->sitemapIndex();
            break;

        case 'sitemap-products':
        case 'sitemap-categories':
        case 'sitemap-brands':
        case 'sitemap-goals':
        case 'sitemap-articles':
        case 'sitemap-pages':
        case 'sitemap-static':
            (new SeoController())->sitemap(
                str_replace('sitemap-', '', $route),
                max(1, query_int('page', 1))
            );
            break;

        case 'feed':
            (new SeoController())->feed();
            break;

        // ---------------------------------------------------------------- admin
        case 'admin':
            (new AdminController())->dashboard();
            break;

        case 'admin/products':
            (new AdminController())->products();
            break;

        case 'admin/product':
            (new AdminController())->productForm();
            break;

        case 'admin/product/save':
            (new AdminController())->saveProduct();
            break;

        case 'admin/product/delete':
            (new AdminController())->deleteProduct();
            break;

        case 'admin/orders':
            (new AdminController())->orders();
            break;

        case 'admin/order':
            (new AdminController())->order();
            break;

        case 'admin/order/status':
            (new AdminController())->updateOrderStatus();
            break;

        case 'admin/coupons':
            (new AdminController())->coupons();
            break;

        case 'admin/coupon/save':
            (new AdminController())->saveCoupon();
            break;

        case 'admin/coupon/delete':
            (new AdminController())->deleteCoupon();
            break;

        case 'admin/messages':
            (new AdminController())->messages();
            break;

        case 'admin/message/delete':
            (new AdminController())->deleteMessage();
            break;

        case 'admin/reviews':
            (new AdminController())->reviews();
            break;

        case 'admin/review/status':
            (new AdminController())->updateReviewStatus();
            break;

        case 'admin/review/delete':
            (new AdminController())->deleteReview();
            break;

        case 'admin/content':
            (new AdminContentController())->index();
            break;

        case 'admin/content/edit':
            (new AdminContentController())->form();
            break;

        case 'admin/content/save':
            (new AdminContentController())->save();
            break;

        case 'admin/content/delete':
            (new AdminContentController())->delete();
            break;

        case 'admin/users':
            (new AdminController())->users();
            break;

        case 'admin/user/role':
            (new AdminController())->updateUserRole();
            break;

        case 'admin/user/delete':
            (new AdminController())->deleteUser();
            break;

        case '__not_found__':
        default:
            $store->notFound();
    }
} catch (Throwable $e) {
    if ((bool) config('app.debug')) {
        http_response_code(500);
        echo '<pre style="padding:24px;font-family:monospace;white-space:pre-wrap;">';
        echo htmlspecialchars('Application error: ' . $e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString());
        echo '</pre>';
    } else {
        http_response_code(500);
        echo 'Something went wrong. Please try again later.';
    }
}
