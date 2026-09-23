<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';

require_login();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$user = current_user();

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<main class="main">
    <div class="content">
        <h1>Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
        <p>Email: <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <p>Role: <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></p>

        <form method="POST" action="/auth-handler.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit">Logout</button>
        </form>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
