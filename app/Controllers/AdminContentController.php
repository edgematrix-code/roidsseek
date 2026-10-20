<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\View;
use PDOException;

/**
 * Generic CRUD console for the smaller catalogue and content tables.
 *
 * Every writable column is declared in the entity map below, so request data
 * is only ever mapped onto known columns (never interpolated table/column
 * names from user input).
 */
class AdminContentController
{
    /** @var array<string,array<string,mixed>> */
    private const ENTITIES = [
        'categories' => [
            'label' => 'Categories',
            'singular' => 'category',
            'table' => 'categories',
            'fields' => [
                'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'hint' => 'Leave blank to generate it from the name.'],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
                'sort_order' => ['label' => 'Sort order', 'type' => 'number'],
            ],
            'columns' => [
                'name' => ['label' => 'Name'],
                'slug' => ['label' => 'Slug'],
                'sort_order' => ['label' => 'Order'],
            ],
            'order' => 'sort_order ASC, name ASC',
            'search' => ['name', 'slug'],
        ],
        'brands' => [
            'label' => 'Brands',
            'singular' => 'brand',
            'table' => 'brands',
            'fields' => [
                'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'hint' => 'Leave blank to generate it from the name.'],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
            ],
            'columns' => [
                'name' => ['label' => 'Name'],
                'slug' => ['label' => 'Slug'],
                'description' => ['label' => 'Description', 'type' => 'excerpt'],
            ],
            'order' => 'name ASC',
            'search' => ['name', 'slug'],
        ],
        'goals' => [
            'label' => 'Goals',
            'singular' => 'goal',
            'table' => 'goals',
            'fields' => [
                'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'hint' => 'Leave blank to generate it from the name.'],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
            ],
            'columns' => [
                'name' => ['label' => 'Name'],
                'slug' => ['label' => 'Slug'],
                'description' => ['label' => 'Description', 'type' => 'excerpt'],
            ],
            'order' => 'name ASC',
            'search' => ['name', 'slug'],
        ],
        'articles' => [
            'label' => 'Articles',
            'singular' => 'article',
            'table' => 'articles',
            'fields' => [
                'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'hint' => 'Leave blank to generate it from the title.'],
                'excerpt' => ['label' => 'Excerpt', 'type' => 'textarea', 'rows' => 3],
                'body' => ['label' => 'Body', 'type' => 'textarea', 'rows' => 14],
                'image' => ['label' => 'Image path', 'type' => 'text', 'hint' => 'Relative to the site root, e.g. assets/images/article.webp'],
                'published_at' => ['label' => 'Published date', 'type' => 'date'],
                'is_published' => ['label' => 'Published', 'type' => 'checkbox'],
            ],
            'columns' => [
                'title' => ['label' => 'Title'],
                'slug' => ['label' => 'Slug'],
                'published_at' => ['label' => 'Date'],
                'is_published' => ['label' => 'Status', 'type' => 'boolean'],
            ],
            'order' => 'published_at DESC, id DESC',
            'search' => ['title', 'slug'],
        ],
        'events' => [
            'label' => 'Events',
            'singular' => 'event',
            'table' => 'events',
            'fields' => [
                'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'hint' => 'Leave blank to generate it from the title.'],
                'excerpt' => ['label' => 'Excerpt', 'type' => 'textarea', 'rows' => 4],
                'image' => ['label' => 'Image path', 'type' => 'text', 'hint' => 'Relative to the site root, e.g. assets/images/event.webp'],
                'published_at' => ['label' => 'Published date', 'type' => 'date'],
            ],
            'columns' => [
                'title' => ['label' => 'Title'],
                'slug' => ['label' => 'Slug'],
                'published_at' => ['label' => 'Date'],
            ],
            'order' => 'published_at DESC, id DESC',
            'search' => ['title', 'slug'],
        ],
        'faqs' => [
            'label' => 'FAQs',
            'singular' => 'FAQ',
            'table' => 'faqs',
            'fields' => [
                'question' => ['label' => 'Question', 'type' => 'text', 'required' => true],
                'answer' => ['label' => 'Answer', 'type' => 'textarea', 'rows' => 7, 'required' => true],
                'sort_order' => ['label' => 'Sort order', 'type' => 'number'],
            ],
            'columns' => [
                'question' => ['label' => 'Question'],
                'sort_order' => ['label' => 'Order'],
            ],
            'order' => 'sort_order ASC, id ASC',
            'search' => ['question'],
        ],
        'pages' => [
            'label' => 'Pages',
            'singular' => 'page',
            'table' => 'pages',
            'fields' => [
                'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'hint' => 'Used in the page URL: index.php?r=page&slug=... Leave blank to generate it from the title.'],
                'content' => ['label' => 'Content (HTML allowed)', 'type' => 'textarea', 'rows' => 16],
            ],
            'columns' => [
                'title' => ['label' => 'Title'],
                'slug' => ['label' => 'Slug'],
            ],
            'order' => 'title ASC',
            'search' => ['title', 'slug'],
        ],
    ];

    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): void
    {
        [$key, $entity] = $this->resolve();
        $search = trim((string) ($_GET['q'] ?? ''));

        $where = '1 = 1';
        $params = [];
        if ($search !== '') {
            $clauses = [];
            foreach ($entity['search'] as $column) {
                $clauses[] = $column . ' LIKE ?';
                $params[] = '%' . $search . '%';
            }
            $where = '(' . implode(' OR ', $clauses) . ')';
        }

        View::render('admin/content-list', [
            'title' => $entity['label'] . ' - Admin',
            'entityKey' => $key,
            'entity' => $entity,
            'rows' => Database::all(
                'SELECT * FROM ' . $entity['table'] . ' WHERE ' . $where . ' ORDER BY ' . $entity['order'],
                $params
            ),
            'search' => $search,
        ], 'admin/layout');
    }

    public function form(): void
    {
        [$key, $entity] = $this->resolve();
        $id = query_int('id', 0);
        $row = null;
        if ($id > 0) {
            $row = Database::one('SELECT * FROM ' . $entity['table'] . ' WHERE id = ?', [$id]);
            if ($row === null) {
                flash('error', $entity['singular'] . ' not found.');
                redirect('admin/content', ['entity' => $key]);
            }
        }

        View::render('admin/content-form', [
            'title' => ($row ? 'Edit ' : 'New ') . $entity['singular'],
            'entityKey' => $key,
            'entity' => $entity,
            'row' => $row,
            'errors' => [],
        ], 'admin/layout');
    }

    public function save(): void
    {
        if (!is_post()) {
            redirect('admin/content');
        }
        verify_csrf();

        [$key, $entity] = $this->resolve();
        $id = (int) input('id', 0);
        $existing = $id > 0
            ? Database::one('SELECT * FROM ' . $entity['table'] . ' WHERE id = ?', [$id])
            : null;

        if ($id > 0 && $existing === null) {
            flash('error', $entity['singular'] . ' not found.');
            redirect('admin/content', ['entity' => $key]);
        }

        $values = [];
        $errors = [];

        foreach ($entity['fields'] as $column => $field) {
            $type = $field['type'];
            if ($type === 'checkbox') {
                $values[$column] = input($column) ? 1 : 0;
                continue;
            }

            $raw = trim((string) input($column, ''));
            if ($type === 'number') {
                $values[$column] = $raw === '' ? 0 : (int) $raw;
                continue;
            }
            if ($type === 'date') {
                $values[$column] = $raw === '' ? null : $raw;
                continue;
            }
            $values[$column] = $raw;

            if (!empty($field['required']) && $raw === '') {
                $errors[$column] = $field['label'] . ' is required.';
            }
        }

        // Generate a slug when one is expected but not provided.
        if (isset($values['slug'])) {
            if ($values['slug'] === '') {
                $source = $values['title'] ?? $values['name'] ?? $values['question'] ?? '';
                $values['slug'] = $this->slugify((string) $source);
            }
            if ($values['slug'] === '') {
                $errors['slug'] = 'Slug is required.';
            } else {
                $dupe = Database::value(
                    'SELECT id FROM ' . $entity['table'] . ' WHERE slug = ? AND id <> ?',
                    [$values['slug'], $id]
                );
                if ($dupe !== null) {
                    $errors['slug'] = 'Another ' . $entity['singular'] . ' already uses that slug.';
                }
            }
        }

        if ($errors !== []) {
            View::render('admin/content-form', [
                'title' => ($existing ? 'Edit ' : 'New ') . $entity['singular'],
                'entityKey' => $key,
                'entity' => $entity,
                'row' => array_merge(['id' => $id], $values),
                'errors' => $errors,
            ], 'admin/layout');
            return;
        }

        try {
            if ($existing !== null) {
                $assignments = implode(', ', array_map(static fn ($column) => $column . ' = ?', array_keys($values)));
                Database::run(
                    'UPDATE ' . $entity['table'] . ' SET ' . $assignments . ' WHERE id = ?',
                    [...array_values($values), $id]
                );
                flash('success', ucfirst($entity['singular']) . ' updated.');
            } else {
                $columns = implode(', ', array_keys($values));
                $marks = implode(', ', array_fill(0, count($values), '?'));
                $id = Database::insert(
                    'INSERT INTO ' . $entity['table'] . ' (' . $columns . ') VALUES (' . $marks . ')',
                    array_values($values)
                );
                flash('success', ucfirst($entity['singular']) . ' created.');
            }
        } catch (PDOException $exception) {
            flash('error', 'Could not save that ' . $entity['singular'] . ': ' . $exception->getMessage());
            redirect('admin/content/edit', ['entity' => $key, 'id' => $id]);
        }

        redirect('admin/content', ['entity' => $key]);
    }

    public function delete(): void
    {
        if (!is_post()) {
            redirect('admin/content');
        }
        verify_csrf();

        [$key, $entity] = $this->resolve();
        $id = (int) input('id', 0);

        try {
            Database::run('DELETE FROM ' . $entity['table'] . ' WHERE id = ?', [$id]);
            flash('success', ucfirst($entity['singular']) . ' deleted.');
        } catch (PDOException $exception) {
            flash('error', 'Could not delete that ' . $entity['singular'] . ': ' . $exception->getMessage());
        }

        redirect('admin/content', ['entity' => $key]);
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function resolve(): array
    {
        $key = (string) ($_GET['entity'] ?? 'categories');
        if (!isset(self::ENTITIES[$key])) {
            $key = 'categories';
        }

        return [$key, self::ENTITIES[$key]];
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

        return trim($text, '-');
    }
}
