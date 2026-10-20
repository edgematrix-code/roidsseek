<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Database;
use App\Support\Nav;
use App\Support\View;

class StoreController
{
    private const PER_PAGE = 24;


    public function home(): void
    {
        $featured = Database::all(
            'SELECT p.*, b.name AS brand_name FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.is_active = 1 AND p.is_featured = 1
             ORDER BY p.old_price IS NULL, p.price ASC LIMIT 8'
        );

        if ($featured === []) {
            $featured = Database::all(
                'SELECT p.*, b.name AS brand_name FROM products p
                 LEFT JOIN brands b ON b.id = p.brand_id
                 WHERE p.is_active = 1 ORDER BY p.id LIMIT 8'
            );
        }

        $deals = Database::all(
            'SELECT p.*, b.name AS brand_name FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.is_active = 1 AND p.old_price IS NOT NULL AND p.old_price > p.price
             ORDER BY (p.old_price - p.price) DESC LIMIT 8'
        );

        $articles = Database::all('SELECT * FROM articles WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3');
        $events = Database::all('SELECT * FROM events ORDER BY published_at DESC LIMIT 3');
        $reviews = Database::all(
            'SELECT r.*, p.name AS product_name, p.slug AS product_slug
             FROM reviews r JOIN products p ON p.id = r.product_id
             WHERE r.status = "approved" ORDER BY r.created_at DESC LIMIT 8'
        );

        View::render('pages/home', [
            'title' => 'Buy Steroids Online | Anabolic Steroids for Sale - RoidsSeek',
            'featured' => $featured,
            'deals' => $deals,
            'articles' => $articles,
            'events' => $events,
            'reviews' => $reviews,
            'categories' => Nav::categories(),
        ]);
    }

    /** Product listing for categories, brands, goals, shipping and search. */
    public function listing(string $type, ?string $slug = null): void
    {
        [$title, $description, $where, $params, $route, $routeParams, $facetScope] = $this->resolveScope($type, $slug);

        $filters = $this->filters();
        $whereSql = $where;
        $paramList = $params;

        if ($filters['brand'] !== []) {
            $marks = implode(',', array_fill(0, count($filters['brand']), '?'));
            $whereSql .= " AND p.brand_id IN ($marks)";
            $paramList = array_merge($paramList, $filters['brand']);
        }

        if ($filters['substance'] !== []) {
            $marks = implode(',', array_fill(0, count($filters['substance']), '?'));
            $whereSql .= " AND p.substance IN ($marks)";
            $paramList = array_merge($paramList, $filters['substance']);
        }

        if ($filters['location'] !== null) {
            $whereSql .= ' AND p.shipping_location IN (?, "both")';
            $paramList[] = $filters['location'];
        }

        if ($filters['max_price'] !== null) {
            $whereSql .= ' AND p.price <= ?';
            $paramList[] = $filters['max_price'];
        }

        if ($filters['stock'] === 'in') {
            $whereSql .= ' AND p.stock > 0';
        }

        $total = (int) Database::value(
            "SELECT COUNT(DISTINCT p.id) FROM products p WHERE $whereSql",
            $paramList
        );

        $page = max(1, query_int('page', 1));
        $pager = paginate($total, self::PER_PAGE, $page);

        $orderBy = match ($filters['sort']) {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name_asc' => 'p.name ASC',
            'name_desc' => 'p.name DESC',
            'newest' => 'p.id DESC',
            default => 'p.old_price IS NOT NULL DESC, p.id ASC',
        };

        $products = Database::all(
            "SELECT p.*, b.name AS brand_name FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE $whereSql
             ORDER BY $orderBy
             LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
            $paramList
        );

        View::render('pages/listing', [
            'title' => $title . ' - RoidsSeek',
            'heading' => $title,
            'description' => $description,
            'products' => $products,
            'pager' => $pager,
            'filters' => $filters,
            'route' => $route,
            'routeParams' => $routeParams,
            'brands' => $this->facetBrands($facetScope[0], $facetScope[1]),
            'substances' => $this->facetSubstances($facetScope[0], $facetScope[1]),
            'type' => $type,
            'sale' => $type === 'sale',
        ]);
    }

    public function product(string $slug): void
    {
        $product = Database::one(
            'SELECT p.*, b.name AS brand_name, b.slug AS brand_slug
             FROM products p LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.slug = ? AND p.is_active = 1',
            [$slug]
        );

        if ($product === null) {
            $this->notFound();
            return;
        }

        $categories = Database::all(
            'SELECT c.* FROM categories c
             JOIN product_category pc ON pc.category_id = c.id
             WHERE pc.product_id = ? ORDER BY c.sort_order',
            [$product['id']]
        );

        $goals = Database::all(
            'SELECT g.* FROM goals g JOIN product_goal pg ON pg.goal_id = g.id WHERE pg.product_id = ?',
            [$product['id']]
        );

        $reviews = Database::all(
            'SELECT * FROM reviews WHERE product_id = ? AND status = "approved" ORDER BY created_at DESC LIMIT 20',
            [$product['id']]
        );

        $rating = Database::one(
            'SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id = ? AND status = "approved"',
            [$product['id']]
        );

        $related = [];
        if ($categories !== []) {
            $related = Database::all(
                'SELECT DISTINCT p.*, b.name AS brand_name FROM products p
                 LEFT JOIN brands b ON b.id = p.brand_id
                 JOIN product_category pc ON pc.product_id = p.id
                 WHERE pc.category_id = ? AND p.id <> ? AND p.is_active = 1
                 ORDER BY RAND() LIMIT 4',
                [$categories[0]['id'], $product['id']]
            );
        }

        View::render('pages/product', [
            'title' => $product['name'] . ' - RoidsSeek',
            'product' => $product,
            'categories' => $categories,
            'goals' => $goals,
            'reviews' => $reviews,
            'rating' => $rating,
            'related' => $related,
        ]);
    }

    public function faq(): void
    {
        View::render('pages/faq', [
            'title' => 'FAQ - RoidsSeek',
            'faqs' => Database::all('SELECT * FROM faqs ORDER BY sort_order, id'),
        ]);
    }

    public function articles(): void
    {
        View::render('pages/articles', [
            'title' => 'Articles - RoidsSeek',
            'articles' => Database::all('SELECT * FROM articles WHERE is_published = 1 ORDER BY published_at DESC'),
        ]);
    }

    public function article(string $slug): void
    {
        $article = Database::one('SELECT * FROM articles WHERE slug = ? AND is_published = 1', [$slug]);
        if ($article === null) {
            $this->notFound();
            return;
        }

        View::render('pages/article', [
            'title' => $article['title'] . ' - RoidsSeek',
            'article' => $article,
            'more' => Database::all(
                'SELECT * FROM articles WHERE is_published = 1 AND id <> ? ORDER BY published_at DESC LIMIT 3',
                [$article['id']]
            ),
        ]);
    }

    public function events(): void
    {
        View::render('pages/events', [
            'title' => 'Events - RoidsSeek',
            'events' => Database::all('SELECT * FROM events ORDER BY published_at DESC'),
        ]);
    }

    public function page(string $slug): void
    {
        $page = Database::one('SELECT * FROM pages WHERE slug = ?', [$slug]);
        if ($page === null) {
            $this->notFound();
            return;
        }

        View::render('pages/page', [
            'title' => $page['title'] . ' - RoidsSeek',
            'page' => $page,
        ]);
    }

    public function contact(): void
    {
        $errors = [];

        if (is_post()) {
            verify_csrf();
            $name = (string) input('name');
            $email = (string) input('email');
            $subject = (string) input('subject');
            $message = (string) input('message');

            if ($name === '') {
                $errors['name'] = 'Please tell us your name.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            }
            if (strlen($message) < 10) {
                $errors['message'] = 'Please write at least 10 characters.';
            }

            if ($errors === []) {
                Database::insert(
                    'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)',
                    [$name, $email, $subject, $message]
                );
                clear_old();
                flash('success', 'Thanks for reaching out. We reply to every message within 24 hours.');
                redirect('contact');
            }

            remember_old($_POST);
        }

        View::render('pages/contact', [
            'title' => 'Contact Us - RoidsSeek',
            'errors' => $errors,
        ]);
    }

    public function newsletter(): void
    {
        if (!is_post()) {
            redirect('');
        }

        verify_csrf();
        $email = (string) input('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.');
            redirect('');
        }

        Database::run('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)', [strtolower($email)]);
        flash('success', 'You are subscribed. Watch your inbox for the next drop.');
        redirect('');
    }

    public function review(string $slug): void
    {
        $product = Database::one('SELECT id FROM products WHERE slug = ?', [$slug]);
        if ($product === null) {
            $this->notFound();
            return;
        }

        if (!is_post()) {
            redirect('product', ['slug' => $slug]);
        }

        verify_csrf();
        $name = (string) input('name');
        $rating = max(1, min(5, (int) input('rating', 5)));
        $title = (string) input('title');
        $body = (string) input('body');

        if ($name === '' || strlen($body) < 10) {
            flash('error', 'Please add your name and at least 10 characters of feedback.');
            redirect('product', ['slug' => $slug]);
        }

        Database::insert(
            'INSERT INTO reviews (product_id, user_id, name, rating, title, body, status)
             VALUES (?, ?, ?, ?, ?, ?, "pending")',
            [$product['id'], user()['id'] ?? null, $name, $rating, $title, $body]
        );

        flash('success', 'Thanks! Your review will appear once it has been approved.');
        redirect('product', ['slug' => $slug]);
    }

    public function notFound(): void
    {
        http_response_code(404);
        View::render('pages/404', ['title' => 'Page not found - RoidsSeek']);
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    private function filters(): array
    {
        $brand = array_filter(array_map('intval', (array) ($_GET['brand'] ?? [])));
        $substance = array_filter(array_map('trim', (array) ($_GET['substance'] ?? [])));
        $location = $_GET['location'] ?? null;
        if (!in_array($location, ['usa', 'international'], true)) {
            $location = null;
        }

        $maxPrice = $_GET['max_price'] ?? null;
        $maxPrice = is_numeric($maxPrice) ? (float) $maxPrice : null;

        $sort = (string) ($_GET['sort'] ?? 'featured');
        if (!in_array($sort, ['featured', 'price_asc', 'price_desc', 'name_asc', 'name_desc', 'newest'], true)) {
            $sort = 'featured';
        }

        return [
            'brand' => array_values(array_unique($brand)),
            'substance' => array_values(array_unique($substance)),
            'location' => $location,
            'max_price' => $maxPrice,
            'stock' => $_GET['stock'] ?? null,
            'sort' => $sort,
        ];
    }

    /**
     * Resolve the listing scope into SQL conditions.
     *
     * @return array{0:string,1:string,2:string,3:array,4:string,5:array,6:array}
     */
    private function resolveScope(string $type, ?string $slug): array
    {
        switch ($type) {
            case 'category':
                $row = Database::one('SELECT * FROM categories WHERE slug = ?', [$slug]);
                if ($row === null) {
                    $this->notFound();
                    exit;
                }
                return [
                    $row['name'],
                    (string) ($row['description'] ?? ''),
                    'p.is_active = 1 AND EXISTS (SELECT 1 FROM product_category pc WHERE pc.product_id = p.id AND pc.category_id = ?)',
                    [$row['id']],
                    'category',
                    ['slug' => $row['slug']],
                    ['category', $row['id']],
                ];
            case 'brand':
                $row = Database::one('SELECT * FROM brands WHERE slug = ?', [$slug]);
                if ($row === null) {
                    $this->notFound();
                    exit;
                }
                return [
                    $row['name'],
                    (string) ($row['description'] ?? ''),
                    'p.is_active = 1 AND p.brand_id = ?',
                    [$row['id']],
                    'brand',
                    ['slug' => $row['slug']],
                    ['brand', $row['id']],
                ];
            case 'goal':
                $row = Database::one('SELECT * FROM goals WHERE slug = ?', [$slug]);
                if ($row === null) {
                    $this->notFound();
                    exit;
                }
                return [
                    $row['name'],
                    (string) ($row['description'] ?? ''),
                    'p.is_active = 1 AND EXISTS (SELECT 1 FROM product_goal pg WHERE pg.product_id = p.id AND pg.goal_id = ?)',
                    [$row['id']],
                    'goal',
                    ['slug' => $row['slug']],
                    ['goal', $row['id']],
                ];
            case 'shipping':
                $slug = $slug === 'usa' ? 'usa' : 'international';
                return [
                    $slug === 'usa' ? 'USA Domestic Shipping' : 'International Shipping',
                    'Products shipped from our ' . ($slug === 'usa' ? 'US domestic' : 'international') . ' warehouse.',
                    'p.is_active = 1 AND p.shipping_location IN (?, "both")',
                    [$slug],
                    'shipping',
                    ['slug' => $slug],
                    ['shipping', $slug],
                ];
            case 'sale':
                return [
                    'Best Deals',
                    'Limited-time discounts across the catalogue.',
                    'p.is_active = 1 AND p.old_price IS NOT NULL AND p.old_price > p.price',
                    [],
                    'sale',
                    [],
                    ['sale', null],
                ];
            case 'search':
                $q = trim((string) ($_GET['q'] ?? $_GET['keyword'] ?? ''));
                return [
                    $q === '' ? 'Search' : 'Search results for "' . $q . '"',
                    '',
                    'p.is_active = 1 AND (p.name LIKE ? OR p.substance LIKE ? OR p.sku LIKE ? OR p.pharmaceutical_name LIKE ?)',
                    ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%', '%' . $q . '%'],
                    'search',
                    ['q' => $q],
                    ['search', $q],
                ];
            case 'all':
            default:
                return [
                    'All Products',
                    'The complete RoidsSeek catalogue.',
                    'p.is_active = 1',
                    [],
                    'products',
                    [],
                    ['all', null],
                ];
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function facetBrands(string $scope, $value): array
    {
        [$join, $where, $params] = $this->scopeJoin($scope, $value);

        return Database::all(
            "SELECT b.id, b.name, b.slug, COUNT(DISTINCT p.id) AS total
             FROM brands b JOIN products p ON p.brand_id = b.id $join
             WHERE $where AND p.is_active = 1
             GROUP BY b.id, b.name, b.slug ORDER BY b.name",
            $params
        );
    }

    /** @return array<int,array<string,mixed>> */
    private function facetSubstances(string $scope, $value): array
    {
        [$join, $where, $params] = $this->scopeJoin($scope, $value);

        return Database::all(
            "SELECT p.substance, COUNT(DISTINCT p.id) AS total
             FROM products p $join
             WHERE $where AND p.is_active = 1 AND p.substance IS NOT NULL AND p.substance <> ''
             GROUP BY p.substance ORDER BY p.substance",
            $params
        );
    }

    private function scopeJoin(string $scope, $value): array
    {
        switch ($scope) {
            case 'category':
                return [
                    'JOIN product_category pc ON pc.product_id = p.id',
                    'pc.category_id = ?',
                    [$value],
                ];
            case 'brand':
                return ['', 'p.brand_id = ?', [$value]];
            case 'goal':
                return [
                    'JOIN product_goal pg ON pg.product_id = p.id',
                    'pg.goal_id = ?',
                    [$value],
                ];
            case 'shipping':
                return ['', 'p.shipping_location IN (?, "both")', [$value]];
            case 'sale':
                return ['', 'p.old_price IS NOT NULL AND p.old_price > p.price', []];
            case 'search':
                return ['', '(p.name LIKE ? OR p.substance LIKE ? OR p.sku LIKE ?)', ['%' . $value . '%', '%' . $value . '%', '%' . $value . '%']];
            default:
                return ['', '1 = 1', []];
        }
    }
}
