<?php
// includes/app.php
// Shared helpers + page shell for the logged-in pages.

require_once __DIR__ . '/nav.php';

if (!defined('PLATFORM_FEE')) {
    define('PLATFORM_FEE', 10.0); // flat fee per checkout (matches transactions.platform_fee default)
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('peso')) {
    function peso($amount): string
    {
        $amount = (float) $amount;
        return '₱' . number_format($amount, $amount == floor($amount) ? 0 : 2);
    }
}

if (!function_exists('cat_class')) {
    function cat_class(string $category): string
    {
        return 'c-' . trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($category)), '-');
    }
}

if (!function_exists('app_header')) {
    function app_header(string $title, string $active = ''): void
    {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> — ShareHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:opsz,wght@6..72,500;6..72,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/app.css">
<?php app_head_assets(); ?>
</head>
<body>
<?php app_nav($active); ?>
<main class="wrap page">
<?php
    }
}

if (!function_exists('app_footer')) {
    function app_footer(): void
    {
        ?>
</main>
<footer class="foot-note">© <?= date('Y') ?> ShareHub. Student project prototype, so payments are simulated.</footer>
</body>
</html>
<?php
    }
}