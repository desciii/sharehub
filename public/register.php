<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';

require_guest();

$pageTitle = 'Register';
$errors = $_SESSION['auth_errors'] ?? [];
$oldName = $_SESSION['old_name'] ?? '';
$oldEmail = $_SESSION['old_email'] ?? '';

unset($_SESSION['auth_errors'], $_SESSION['old_name'], $_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ShareHub</title>
</head>
<body>
    <main>
        <h1>Register</h1>

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
            <input type="hidden" name="action" value="register">

            <div>
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="<?= htmlspecialchars($oldName, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="name" minlength="2" maxlength="100">
            </div>

            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email">
            </div>

            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
            </div>

            <div>
                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="/login.php">Login</a></p>
    </main>
</body>
</html>
