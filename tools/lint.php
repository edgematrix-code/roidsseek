<?php

/**
 * Syntax-check every PHP file in the project.
 *
 *   php tools/lint.php
 *   npm run lint
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = ['app', 'config', 'database', 'tools', 'tests'];
$files = [];
foreach ($directories as $directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
}
foreach (glob($root . '/*.php') ?: [] as $file) {
    $files[] = $file;
}
sort($files);

$failures = 0;
foreach ($files as $file) {
    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);
    if ($status !== 0) {
        $failures++;
        echo "FAIL " . str_replace($root . '/', '', $file) . "\n";
        echo '     ' . implode("\n     ", $output) . "\n";
    }
}

printf("%d file(s) checked, %d failure(s).\n", count($files), $failures);
exit($failures === 0 ? 0 : 1);
