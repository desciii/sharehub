<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';

require_guest();

$pageTitle = 'Login';
$errors = $_SESSION['auth_errors'] ?? [];
$oldEmail = $_SESSION['old_email'] ?? '';
$redirectTo = (string) ($_GET['redirect'] ?? '/index.php');

if (!str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
    $redirectTo = '/index.php';
}

unset($_SESSION['auth_errors'], $_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ShareHub</title>
</head>
<body>
    <main>
        <h1>Login</h1>

        <?php if ($errors): ?>
            <div role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/auth-handler.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email">
            </div>

            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="/register.php">Register</a></p>
    </main>
</body>
</html>
