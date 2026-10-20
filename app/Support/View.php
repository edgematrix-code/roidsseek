<?php

declare(strict_types=1);

namespace App\Support;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layout'): void
    {
        echo self::capture($view, $data, $layout);
    }

    public static function capture(string $view, array $data = [], ?string $layout = 'layout'): string
    {
        $content = self::renderFile($view, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderFile($layout, $data + ['content' => $content]);
    }

    public static function partial(string $view, array $data = []): string
    {
        return self::renderFile($view, $data);
    }

    private static function renderFile(string $view, array $data): string
    {
        $path = dirname(__DIR__) . '/Views/' . $view . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}
