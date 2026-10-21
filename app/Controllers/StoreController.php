<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Database;
use App\Support\JsonLd;
use App\Support\Nav;
use App\Support\Seo;
use App\Support\Site;
use App\Support\View;

class StoreController
{
    private const PER_PAGE = 24;


    public function home(): void
    {
        $featured = Database::all(
            'SELECT p.*, b.name AS brand_name FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.is_active = 1 AND p.is_featured = 1 AND p.is_restricted = 0 AND p.discontinued = 0
             ORDER BY p.old_price IS NULL, p.price ASC LIMIT 8'
        );

        if ($featured === []) {
            $featured = Database::all(
                'SELECT p.*, b.name AS brand_name FROM products p
                 LEFT JOIN brands b ON b.id = p.brand_id
                 WHERE p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
                 ORDER BY p.id LIMIT 8'
            );
        }

        $deals = Database::all(
            'SELECT p.*, b.name AS brand_name FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
               AND p.old_price IS NOT NULL AND p.old_price > p.price
             ORDER BY (p.old_price - p.price) DESC LIMIT 8'
        );

        $articles = Database::all('SELECT * FROM articles WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3');
        $events = Database::all('SELECT * FROM events ORDER BY published_at DESC LIMIT 3');
        $reviews = Database::all(
            'SELECT r.*, p.name AS product_name, p.slug AS product_slug
             FROM reviews r JOIN products p ON p.id = r.product_id
             WHERE r.status = ? AND p.is_restricted = 0 ORDER BY r.created_at DESC LIMIT 8',
            ['approved']
        );

        $store = Site::name();
        $description = Seo::clip((string) Site::get('defaultMetaDescription', ''));
        if ($description === '') {
            $description = $store !== ''
                ? 'Browse the ' . $store . ' catalogue, current offers and product availability.'
                : 'Browse the catalogue, current offers and product availability.';
        }

        View::render('pages/home', [
            'seo' => [
                'title' => '', // Seo::page() composes store name + tagline.
                'description' => $description,
                'route' => '',
                'params' => [],
            ],
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
        [$title, $description, $where, $params, $route, $routeParams, $facetScope, $row] = $this->resolveScope($type, $slug);

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
            $whereSql .= ' AND p.shipping_location IN (?, ?)';
            $paramList[] = $filters['location'];
            $paramList[] = 'both';
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

        $faceted = $filters['brand'] !== [] || $filters['substance'] !== [] || $filters['location'] !== null
            || $filters['max_price'] !== null || $filters['stock'] !== null || $filters['sort'] !== 'featured';

        $canonical = Seo::canonicalFor($route, $routeParams, $faceted ? 0 : $page);

        $metaTitle = trim((string) ($row['meta_title'] ?? ''));
        $metaDescription = trim((string) ($row['meta_description'] ?? ''));
        if ($metaTitle === '') {
            $metaTitle = Seo::listingTitle($title, $type === 'goal' ? 'goal' : 'category');
        }
        if ($metaDescription === '') {
            $metaDescription = $description !== ''
                ? $description
                : $total . ' products in ' . $title . '.';
        }

        $crumbs = [['name' => 'Home', 'url' => Site::absoluteRoute('')]];
        if ($type === 'category') {
            $crumbs[] = ['name' => $title, 'url' => Site::absoluteRoute('category', ['slug' => (string) ($row['slug'] ?? '')])];
        }

        View::render('pages/listing', [
            'seo' => [
                'title' => $metaTitle,
                'description' => $metaDescription,
                'route' => $route,
                'params' => $routeParams,
                'canonical' => $canonical,
                'page' => $page,
                'jsonLd' => [
                    JsonLd::collection($title, $canonical, $products, $page),
                    JsonLd::breadcrumbs($crumbs),
                ],
            ],
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
            'total' => $total,
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

        if (!empty($product['discontinued'])) {
            $categoryIds = array_map('intval', array_column($categories, 'id'));
            if ($categoryIds !== []) {
                $marks = implode(',', array_fill(0, count($categoryIds), '?'));
                $replacement = Database::one(
                    "SELECT p.slug FROM products p
                     JOIN product_category pc ON pc.product_id = p.id
                     WHERE pc.category_id IN ($marks) AND p.id <> ?
                       AND p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
                     ORDER BY (p.substance <=> ?) DESC, (p.brand_id <=> ?) DESC, p.id ASC
                     LIMIT 1",
                    [...$categoryIds, (int) $product['id'], $product['substance'], $product['brand_id']]
                );
                if ($replacement !== null) {
                    redirect_to(Site::absoluteRoute('product', ['slug' => (string) $replacement['slug']]), 301);
                }
                redirect_to(Site::absoluteRoute('category', ['slug' => (string) $categories[0]['slug']]), 301);
            }

            http_response_code(410);
            View::render('pages/404', [
                'seo' => [
                    'title' => 'Product no longer available',
                    'robots' => 'noindex,nofollow',
                    'route' => 'product',
                    'status' => 410,
                ],
            ]);
            return;
        }

        $goals = Database::all(
            'SELECT g.* FROM goals g JOIN product_goal pg ON pg.goal_id = g.id WHERE pg.product_id = ?',
            [$product['id']]
        );

        $reviews = Database::all(
            'SELECT * FROM reviews WHERE product_id = ? AND status = ? ORDER BY created_at DESC LIMIT 20',
            [$product['id'], 'approved']
        );

        $rating = Database::one(
            'SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id = ? AND status = ?',
            [$product['id'], 'approved']
        );

        $related = [];
        if ($categories !== []) {
            $related = Database::all(
                'SELECT DISTINCT p.*, b.name AS brand_name FROM products p
                 LEFT JOIN brands b ON b.id = p.brand_id
                 JOIN product_category pc ON pc.product_id = p.id
                 WHERE pc.category_id = ? AND p.id <> ? AND p.is_active = 1
                   AND p.is_restricted = 0 AND p.discontinued = 0
                 ORDER BY RAND() LIMIT 4',
                [$categories[0]['id'], $product['id']]
            );
        }

        $restricted = !empty($product['is_restricted']);
        $inStock = (int) $product['stock'] > 0;
        $currency = Site::currency();

        $crumbs = [['name' => 'Home', 'url' => Site::absoluteRoute('')]];
        if ($categories !== []) {
            $crumbs[] = [
                'name' => (string) $categories[0]['name'],
                'url' => Site::absoluteRoute('category', ['slug' => (string) $categories[0]['slug']]),
            ];
        }
        $crumbs[] = [
            'name' => (string) $product['name'],
            'url' => Site::absoluteRoute('product', ['slug' => (string) $product['slug']]),
        ];

        $metaTitle = trim((string) ($product['meta_title'] ?? ''));
        if ($metaTitle === '') {
            $metaTitle = Seo::productTitle($product, $restricted);
        }
        $metaDescription = trim((string) ($product['meta_description'] ?? ''));
        if ($metaDescription === '') {
            $metaDescription = trim((string) ($product['short_description'] ?: $product['description']));
        }

        $productSchema = JsonLd::product($product, $categories);

        View::render('pages/product', [
            'seo' => [
                'title' => $metaTitle,
                'description' => Seo::clip($metaDescription),
                'route' => 'product',
                'params' => ['slug' => (string) $product['slug']],
                'type' => 'product',
                'image' => (string) ($product['image'] ?? ''),
                'robots' => $restricted
                    ? 'noindex,follow'
                    : Seo::robotsFor('product'),
                'product' => [
                    'price' => number_format((float) $product['price'], 2, '.', ''),
                    'currency' => $currency,
                    'availability' => $inStock ? 'in stock' : 'out of stock',
                ],
                'jsonLd' => array_values(array_filter([
                    $productSchema,
                    JsonLd::breadcrumbs($crumbs),
                ])),
            ],
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
        $faqs = Database::all('SELECT * FROM faqs ORDER BY sort_order, id');
        $description = $faqs !== []
            ? 'Answers to common questions about ordering, shipping, payment and prescriptions.'
            : '';

        View::render('pages/faq', [
            'seo' => [
                'title' => Seo::withStore('Frequently asked questions'),
                'description' => $description,
                'route' => 'faq',
                'jsonLd' => [JsonLd::breadcrumbs([
                    ['name' => 'Home', 'url' => Site::absoluteRoute('')],
                    ['name' => 'FAQ', 'url' => Site::absoluteRoute('faq')],
                ])],
            ],
            'faqs' => $faqs,
        ]);
    }

    public function articles(): void
    {
        View::render('pages/articles', [
            'seo' => [
                'title' => Seo::withStore('Guides and articles'),
                'description' => 'Guides and articles from the ' . (Site::name() !== '' ? Site::name() : 'store') . ' team.',
                'route' => 'articles',
                'jsonLd' => [JsonLd::breadcrumbs([
                    ['name' => 'Home', 'url' => Site::absoluteRoute('')],
                    ['name' => 'Articles', 'url' => Site::absoluteRoute('articles')],
                ])],
            ],
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

        $metaTitle = trim((string) ($article['meta_title'] ?? ''));
        $metaDescription = trim((string) ($article['meta_description'] ?? ''));
        $url = Site::absoluteRoute('article', ['slug' => (string) $article['slug']]);

        View::render('pages/article', [
            'seo' => [
                'title' => $metaTitle !== '' ? $metaTitle : Seo::withStore((string) $article['title']),
                'description' => $metaDescription !== '' ? $metaDescription : (string) ($article['excerpt'] ?? ''),
                'route' => 'article',
                'params' => ['slug' => (string) $article['slug']],
                'type' => 'article',
                'image' => (string) ($article['image'] ?? ''),
                'jsonLd' => [
                    JsonLd::article($article),
                    JsonLd::breadcrumbs([
                        ['name' => 'Home', 'url' => Site::absoluteRoute('')],
                        ['name' => 'Articles', 'url' => Site::absoluteRoute('articles')],
                        ['name' => (string) $article['title'], 'url' => $url],
                    ]),
                ],
            ],
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
            'seo' => [
                'title' => Seo::withStore('News and events'),
                'route' => 'events',
                'jsonLd' => [JsonLd::breadcrumbs([
                    ['name' => 'Home', 'url' => Site::absoluteRoute('')],
                    ['name' => 'Events', 'url' => Site::absoluteRoute('events')],
                ])],
            ],
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

        $metaTitle = trim((string) ($page['meta_title'] ?? ''));
        $metaDescription = trim((string) ($page['meta_description'] ?? ''));
        if ($metaDescription === '') {
            $metaDescription = Seo::clip(strip_tags((string) ($page['content'] ?? '')));
        }

        View::render('pages/page', [
            'seo' => [
                'title' => $metaTitle !== '' ? $metaTitle : Seo::withStore((string) $page['title']),
                'description' => $metaDescription,
                'route' => 'page',
                'params' => ['slug' => (string) $page['slug']],
                'robots' => !empty($page['noindex'])
                    ? 'noindex,follow'
                    : Seo::robotsFor('page'),
                'jsonLd' => [JsonLd::breadcrumbs([
                    ['name' => 'Home', 'url' => Site::absoluteRoute('')],
                    ['name' => (string) $page['title'], 'url' => Site::absoluteRoute('page', ['slug' => (string) $page['slug']])],
                ])],
            ],
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
            'seo' => [
                'title' => Seo::withStore('Contact us'),
                'description' => 'Contact options, support hours and business details.',
                'route' => 'contact',
                'jsonLd' => [JsonLd::breadcrumbs([
                    ['name' => 'Home', 'url' => Site::absoluteRoute('')],
                    ['name' => 'Contact', 'url' => Site::absoluteRoute('contact')],
                ])],
            ],
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
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$product['id'], user()['id'] ?? null, $name, $rating, $title, $body, 'pending']
        );

        flash('success', 'Thanks! Your review will appear once it has been approved.');
        redirect('product', ['slug' => $slug]);
    }

    /** Physical location page; only exists when a staffed address is published. */
    public function location(): void
    {
        if (!(bool) Site::get('hasPhysicalLocation', false)) {
            $this->notFound();
            return;
        }

        $address = (array) Site::get('address', []);
        $description = trim(implode(', ', array_filter([
            (string) ($address['street'] ?? ''),
            (string) ($address['city'] ?? ''),
            (string) ($address['country'] ?? ''),
        ])));

        View::render('pages/location', [
            'seo' => [
                'title' => Seo::withStore('Visit us'),
                'description' => $description !== ''
                    ? 'Location, opening hours and contact details: ' . $description . '.'
                    : 'Location, opening hours and contact details.',
                'route' => 'location',
                'jsonLd' => [JsonLd::breadcrumbs([
                    ['name' => 'Home', 'url' => Site::absoluteRoute('')],
                    ['name' => 'Location', 'url' => Site::absoluteRoute('location')],
                ])],
            ],
            'address' => $address,
        ]);
    }

    public function notFound(): void
    {
        http_response_code(404);
        View::render('pages/404', [
            'seo' => [
                'title' => 'Page not found',
                'robots' => 'noindex,follow',
                'route' => '__404__',
                'status' => 404,
            ],
            'popular' => Database::all(
                'SELECT name, slug FROM categories ORDER BY sort_order LIMIT 6'
            ),
        ]);
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
        $live = 'p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0';

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
                    $live . ' AND EXISTS (SELECT 1 FROM product_category pc WHERE pc.product_id = p.id AND pc.category_id = ?)',
                    [$row['id']],
                    'category',
                    ['slug' => $row['slug']],
                    ['category', $row['id']],
                    $row,
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
                    $live . ' AND p.brand_id = ?',
                    [$row['id']],
                    'brand',
                    ['slug' => $row['slug']],
                    ['brand', $row['id']],
                    $row,
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
                    $live . ' AND EXISTS (SELECT 1 FROM product_goal pg WHERE pg.product_id = p.id AND pg.goal_id = ?)',
                    [$row['id']],
                    'goal',
                    ['slug' => $row['slug']],
                    ['goal', $row['id']],
                    $row,
                ];
            case 'shipping':
                $slug = $slug === 'usa' ? 'usa' : 'international';
                return [
                    $slug === 'usa' ? 'USA Domestic Shipping' : 'International Shipping',
                    'Products shipped from our ' . ($slug === 'usa' ? 'US domestic' : 'international') . ' warehouse.',
                    $live . ' AND p.shipping_location IN (?, ?)',
                    [$slug, 'both'],
                    'shipping',
                    ['slug' => $slug],
                    ['shipping', $slug],
                    null,
                ];
            case 'sale':
                return [
                    'Best Deals',
                    'Limited-time discounts across the catalogue.',
                    $live . ' AND p.old_price IS NOT NULL AND p.old_price > p.price',
                    [],
                    'sale',
                    [],
                    ['sale', null],
                    null,
                ];
            case 'search':
                $q = trim((string) ($_GET['q'] ?? $_GET['keyword'] ?? ''));
                return [
                    $q === '' ? 'Search' : 'Search results for "' . $q . '"',
                    '',
                    $live . ' AND (p.name LIKE ? OR p.substance LIKE ? OR p.sku LIKE ? OR p.pharmaceutical_name LIKE ?)',
                    ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%', '%' . $q . '%'],
                    'search',
                    ['q' => $q],
                    ['search', $q],
                    null,
                ];
            case 'all':
            default:
                return [
                    'All Products',
                    'The complete catalogue.',
                    $live,
                    [],
                    'all',
                    [],
                    ['all', null],
                    null,
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
             WHERE $where AND p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0
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
             WHERE $where AND p.is_active = 1 AND p.is_restricted = 0 AND p.discontinued = 0 AND p.substance IS NOT NULL AND p.substance <> ''
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
                return ['', 'p.shipping_location IN (?, ?)', [$value, 'both']];
            case 'sale':
                return ['', 'p.old_price IS NOT NULL AND p.old_price > p.price', []];
            case 'search':
                return ['', '(p.name LIKE ? OR p.substance LIKE ? OR p.sku LIKE ?)', ['%' . $value . '%', '%' . $value . '%', '%' . $value . '%']];
            default:
                return ['', '1 = 1', []];
        }
    }
}
