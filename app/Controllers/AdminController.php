<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Nav;
use App\Support\View;

class AdminController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function dashboard(): void
    {
        $stats = [
            'products' => (int) Database::value('SELECT COUNT(*) FROM products'),
            'orders' => (int) Database::value('SELECT COUNT(*) FROM orders'),
            'pending_orders' => (int) Database::value("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
            'revenue' => (float) Database::value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ('paid','shipped','completed')"),
            'customers' => (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
            'messages' => (int) Database::value('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'),
            'reviews' => (int) Database::value("SELECT COUNT(*) FROM reviews WHERE status = 'pending'"),
            'subscribers' => (int) Database::value('SELECT COUNT(*) FROM newsletter_subscribers'),
        ];

        View::render('admin/dashboard', [
            'title' => 'Admin Dashboard - RoidsSeek',
            'stats' => $stats,
            'recentOrders' => Database::all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 8'),
            'topProducts' => Database::all(
                'SELECT oi.name, SUM(oi.qty) AS sold, SUM(oi.total) AS revenue
                 FROM order_items oi GROUP BY oi.name ORDER BY sold DESC LIMIT 6'
            ),
        ], 'admin/layout');
    }

    public function products(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, query_int('page', 1));
        $perPage = 20;
        $where = '1 = 1';
        $params = [];

        if ($search !== '') {
            $where .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)';
            $params = array_fill(0, 3, '%' . $search . '%');
        }

        $total = (int) Database::value(
            "SELECT COUNT(*) FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id WHERE $where",
            $params
        );
        $pager = paginate($total, $perPage, $page);

        View::render('admin/products', [
            'title' => 'Products - Admin',
            'products' => Database::all(
                "SELECT p.*, b.name AS brand_name FROM products p
                 LEFT JOIN brands b ON b.id = p.brand_id
                 WHERE $where ORDER BY p.id DESC
                 LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
                $params
            ),
            'pager' => $pager,
            'search' => $search,
        ], 'admin/layout');
    }

    public function productForm(): void
    {
        $id = query_int('id', 0);
        $product = $id > 0
            ? Database::one('SELECT * FROM products WHERE id = ?', [$id])
            : null;

        if ($id > 0 && $product === null) {
            flash('error', 'Product not found.');
            redirect('admin/products');
        }

        $selectedCategories = $product
            ? array_column(Database::all('SELECT category_id FROM product_category WHERE product_id = ?', [$product['id']]), 'category_id')
            : [];
        $selectedGoals = $product
            ? array_column(Database::all('SELECT goal_id FROM product_goal WHERE product_id = ?', [$product['id']]), 'goal_id')
            : [];

        View::render('admin/product-form', [
            'title' => $product ? 'Edit product' : 'New product',
            'product' => $product,
            'brands' => Nav::brands(),
            'categories' => Nav::categories(),
            'goals' => Nav::goals(),
            'selectedCategories' => array_map('intval', $selectedCategories),
            'selectedGoals' => array_map('intval', $selectedGoals),
            'errors' => [],
        ], 'admin/layout');
    }

    public function saveProduct(): void
    {
        if (!is_post()) {
            redirect('admin/products');
        }
        verify_csrf();

        $id = (int) input('id', 0);
        $existing = $id > 0 ? Database::one('SELECT * FROM products WHERE id = ?', [$id]) : null;

        $name = trim((string) input('name', ''));
        $slug = trim((string) input('slug', ''));
        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
            $slug = trim((string) $slug, '-');
        }
        $sku = trim((string) input('sku', ''));
        if ($sku === '') {
            $sku = 'RS-' . strtoupper(substr(md5($name . microtime()), 0, 8));
        }

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($slug === '') {
            $errors['slug'] = 'Slug is required.';
        }
        $dupe = Database::value('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $id]);
        if ($dupe !== null) {
            $errors['slug'] = 'Another product already uses that slug.';
        }
        $dupeSku = Database::value('SELECT id FROM products WHERE sku = ? AND id <> ?', [$sku, $id]);
        if ($dupeSku !== null) {
            $errors['sku'] = 'Another product already uses that SKU.';
        }

        $fields = [
            'sku' => $sku,
            'slug' => $slug,
            'name' => $name,
            'brand_id' => (int) input('brand_id', 0) ?: null,
            'substance' => trim((string) input('substance', '')),
            'pharmaceutical_name' => trim((string) input('pharmaceutical_name', '')),
            'pack' => trim((string) input('pack', '')),
            'strength' => trim((string) input('strength', '')),
            'short_description' => trim((string) input('short_description', '')),
            'description' => trim((string) input('description', '')),
            'price' => (float) input('price', 0),
            'old_price' => input('old_price') !== '' ? (float) input('old_price') : null,
            'stock' => (int) input('stock', 0),
            'image' => trim((string) input('image', '')) ?: null,
            'ribbon' => trim((string) input('ribbon', '')) ?: null,
            'shipping_location' => in_array(input('shipping_location'), ['usa', 'international', 'both'], true)
                ? input('shipping_location') : 'both',
            'is_featured' => input('is_featured') ? 1 : 0,
            'is_active' => input('is_active') ? 1 : 0,
        ];

        if ($errors !== []) {
            View::render('admin/product-form', [
                'title' => $existing ? 'Edit product' : 'New product',
                'product' => $fields + ['id' => $id],
                'brands' => Nav::brands(),
                'categories' => Nav::categories(),
                'goals' => Nav::goals(),
                'selectedCategories' => array_map('intval', (array) ($_POST['categories'] ?? [])),
                'selectedGoals' => array_map('intval', (array) ($_POST['goals'] ?? [])),
                'errors' => $errors,
            ], 'admin/layout');
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        if ($existing !== null) {
            Database::run(
                'UPDATE products SET sku = ?, slug = ?, name = ?, brand_id = ?, substance = ?, pharmaceutical_name = ?,
                    pack = ?, strength = ?, short_description = ?, description = ?, price = ?, old_price = ?, stock = ?,
                    image = ?, ribbon = ?, shipping_location = ?, is_featured = ?, is_active = ? WHERE id = ?',
                [
                    $fields['sku'], $fields['slug'], $fields['name'], $fields['brand_id'], $fields['substance'],
                    $fields['pharmaceutical_name'], $fields['pack'], $fields['strength'], $fields['short_description'],
                    $fields['description'], $fields['price'], $fields['old_price'], $fields['stock'], $fields['image'],
                    $fields['ribbon'], $fields['shipping_location'], $fields['is_featured'], $fields['is_active'], $id,
                ]
            );
            $productId = $id;
        } else {
            $productId = Database::insert(
                'INSERT INTO products (sku, slug, name, brand_id, substance, pharmaceutical_name, pack, strength,
                    short_description, description, price, old_price, stock, image, ribbon, shipping_location,
                    is_featured, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $fields['sku'], $fields['slug'], $fields['name'], $fields['brand_id'], $fields['substance'],
                    $fields['pharmaceutical_name'], $fields['pack'], $fields['strength'], $fields['short_description'],
                    $fields['description'], $fields['price'], $fields['old_price'], $fields['stock'], $fields['image'],
                    $fields['ribbon'], $fields['shipping_location'], $fields['is_featured'], $fields['is_active'],
                ]
            );
        }

        Database::run('DELETE FROM product_category WHERE product_id = ?', [$productId]);
        foreach ((array) ($_POST['categories'] ?? []) as $categoryId) {
            if ((int) $categoryId > 0) {
                Database::run('INSERT IGNORE INTO product_category (product_id, category_id) VALUES (?, ?)', [$productId, (int) $categoryId]);
            }
        }

        Database::run('DELETE FROM product_goal WHERE product_id = ?', [$productId]);
        foreach ((array) ($_POST['goals'] ?? []) as $goalId) {
            if ((int) $goalId > 0) {
                Database::run('INSERT IGNORE INTO product_goal (product_id, goal_id) VALUES (?, ?)', [$productId, (int) $goalId]);
            }
        }

        $pdo->commit();

        flash('success', 'Product saved.');
        redirect('admin/product', ['id' => $productId]);
    }

    public function deleteProduct(): void
    {
        if (!is_post()) {
            redirect('admin/products');
        }
        verify_csrf();

        Database::run('DELETE FROM products WHERE id = ?', [(int) input('id', 0)]);
        flash('success', 'Product deleted.');
        redirect('admin/products');
    }

    public function orders(): void
    {
        $status = (string) ($_GET['status'] ?? '');
        $where = '1 = 1';
        $params = [];
        if (in_array($status, ['pending', 'paid', 'shipped', 'completed', 'cancelled'], true)) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }

        $page = max(1, query_int('page', 1));
        $total = (int) Database::value("SELECT COUNT(*) FROM orders WHERE $where", $params);
        $pager = paginate($total, 20, $page);

        View::render('admin/orders', [
            'title' => 'Orders - Admin',
            'orders' => Database::all(
                "SELECT * FROM orders WHERE $where ORDER BY created_at DESC
                 LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
                $params
            ),
            'pager' => $pager,
            'status' => $status,
        ], 'admin/layout');
    }

    public function order(): void
    {
        $order = Database::one('SELECT * FROM orders WHERE id = ?', [query_int('id', 0)]);
        if ($order === null) {
            flash('error', 'Order not found.');
            redirect('admin/orders');
        }

        View::render('admin/order', [
            'title' => 'Order ' . $order['order_number'],
            'order' => $order,
            'items' => Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]),
            'customer' => $order['user_id'] ? Database::one('SELECT * FROM users WHERE id = ?', [$order['user_id']]) : null,
        ], 'admin/layout');
    }

    public function updateOrderStatus(): void
    {
        if (!is_post()) {
            redirect('admin/orders');
        }
        verify_csrf();

        $status = (string) input('status', 'pending');
        if (!in_array($status, ['pending', 'paid', 'shipped', 'completed', 'cancelled'], true)) {
            flash('error', 'Unknown status.');
            redirect('admin/orders');
        }

        $id = (int) input('id', 0);
        Database::run('UPDATE orders SET status = ? WHERE id = ?', [$status, $id]);
        flash('success', 'Order status updated to ' . $status . '.');
        redirect('admin/order', ['id' => $id]);
    }

    public function coupons(): void
    {
        View::render('admin/coupons', [
            'title' => 'Coupons - Admin',
            'coupons' => Database::all('SELECT * FROM coupons ORDER BY id DESC'),
        ], 'admin/layout');
    }

    public function saveCoupon(): void
    {
        if (!is_post()) {
            redirect('admin/coupons');
        }
        verify_csrf();

        $code = strtoupper(trim((string) input('code', '')));
        $type = input('type') === 'fixed' ? 'fixed' : 'percent';
        $value = (float) input('value', 0);

        if ($code === '' || $value <= 0) {
            flash('error', 'A coupon needs a code and a positive value.');
            redirect('admin/coupons');
        }

        $id = (int) input('id', 0);
        $payload = [
            $code, $type, $value,
            (float) input('min_subtotal', 0),
            input('expires_at') !== '' ? input('expires_at') : null,
            input('usage_limit') !== '' ? (int) input('usage_limit') : null,
            input('is_active') ? 1 : 0,
        ];

        if ($id > 0) {
            Database::run(
                'UPDATE coupons SET code = ?, type = ?, value = ?, min_subtotal = ?, expires_at = ?, usage_limit = ?, is_active = ? WHERE id = ?',
                [...$payload, $id]
            );
            flash('success', 'Coupon updated.');
        } else {
            Database::run(
                'INSERT INTO coupons (code, type, value, min_subtotal, expires_at, usage_limit, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE type = VALUES(type), value = VALUES(value), min_subtotal = VALUES(min_subtotal),
                    expires_at = VALUES(expires_at), usage_limit = VALUES(usage_limit), is_active = VALUES(is_active)',
                $payload
            );
            flash('success', 'Coupon created.');
        }

        redirect('admin/coupons');
    }

    public function deleteCoupon(): void
    {
        if (!is_post()) {
            redirect('admin/coupons');
        }
        verify_csrf();

        Database::run('DELETE FROM coupons WHERE id = ?', [(int) input('id', 0)]);
        flash('success', 'Coupon deleted.');
        redirect('admin/coupons');
    }

    public function messages(): void
    {
        Database::run('UPDATE contact_messages SET is_read = 1 WHERE id = ?', [query_int('read', 0)]);
        View::render('admin/messages', [
            'title' => 'Messages - Admin',
            'messages' => Database::all('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100'),
        ], 'admin/layout');
    }

    public function deleteMessage(): void
    {
        if (!is_post()) {
            redirect('admin/messages');
        }
        verify_csrf();

        Database::run('DELETE FROM contact_messages WHERE id = ?', [(int) input('id', 0)]);
        flash('success', 'Message deleted.');
        redirect('admin/messages');
    }

    public function reviews(): void
    {
        View::render('admin/reviews', [
            'title' => 'Reviews - Admin',
            'reviews' => Database::all(
                'SELECT r.*, p.name AS product_name, p.slug AS product_slug
                 FROM reviews r JOIN products p ON p.id = r.product_id
                 ORDER BY r.status = ? DESC, r.created_at DESC LIMIT 100',
                ['pending']
            ),
        ], 'admin/layout');
    }

    public function updateReviewStatus(): void
    {
        if (!is_post()) {
            redirect('admin/reviews');
        }
        verify_csrf();

        $status = (string) input('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            flash('error', 'Unknown status.');
            redirect('admin/reviews');
        }

        Database::run('UPDATE reviews SET status = ? WHERE id = ?', [$status, (int) input('id', 0)]);
        flash('success', 'Review ' . $status . '.');
        redirect('admin/reviews');
    }

    public function deleteReview(): void
    {
        if (!is_post()) {
            redirect('admin/reviews');
        }
        verify_csrf();

        Database::run('DELETE FROM reviews WHERE id = ?', [(int) input('id', 0)]);
        flash('success', 'Review deleted.');
        redirect('admin/reviews');
    }

    public function users(): void
    {
        View::render('admin/users', [
            'title' => 'Customers - Admin',
            'users' => Database::all(
                'SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                        (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.user_id = u.id) AS lifetime_value
                 FROM users u ORDER BY u.created_at DESC'
            ),
        ], 'admin/layout');
    }

    public function updateUserRole(): void
    {
        if (!is_post()) {
            redirect('admin/users');
        }
        verify_csrf();

        $id = (int) input('id', 0);
        $role = (string) input('role', 'customer');
        if (!in_array($role, ['customer', 'admin'], true)) {
            flash('error', 'Unknown role.');
            redirect('admin/users');
        }
        if ($id === Auth::id()) {
            flash('error', 'You cannot change your own role.');
            redirect('admin/users');
        }

        $target = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        if ($target === null) {
            flash('error', 'Account not found.');
            redirect('admin/users');
        }
        if ($target['role'] === 'admin' && $role === 'customer'
            && (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin'") <= 1) {
            flash('error', 'At least one admin account must remain.');
            redirect('admin/users');
        }

        Database::run('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
        flash('success', 'Role updated to ' . $role . '.');
        redirect('admin/users');
    }

    public function deleteUser(): void
    {
        if (!is_post()) {
            redirect('admin/users');
        }
        verify_csrf();

        $id = (int) input('id', 0);
        if ($id === Auth::id()) {
            flash('error', 'You cannot delete your own account.');
            redirect('admin/users');
        }

        $target = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        if ($target === null) {
            flash('error', 'Account not found.');
            redirect('admin/users');
        }
        if ($target['role'] === 'admin'
            && (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin'") <= 1) {
            flash('error', 'At least one admin account must remain.');
            redirect('admin/users');
        }

        Database::run('DELETE FROM users WHERE id = ?', [$id]);
        flash('success', 'Account deleted.');
        redirect('admin/users');
    }
}
