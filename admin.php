<?php

declare(strict_types=1);

// Direct entry point for the admin console: /admin.php
// Any explicit ?r= route is preserved, otherwise the dashboard is shown.
if (!isset($_GET['r']) || trim((string) $_GET['r'], '/') === '') {
    $_GET['r'] = 'admin';
}

require __DIR__ . '/index.php';
