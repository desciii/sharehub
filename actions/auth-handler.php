<?php
// actions/auth-handler.php
// Handles login, registration, and logout POST requests.

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

if (!verify_csrf()) {
    abort_csrf();
}

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    if (is_logged_in()) {
        redirect('/index.php');
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $redirectTo = (string) ($_POST['redirect'] ?? '/index.php');

    if (!str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
        $redirectTo = '/index.php';
    }

    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (!$errors) {
        $stmt = $db->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } else {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $update->execute([$newHash, $user['id']]);
            }

            login_user($user);
            redirect($redirectTo);
        }
    }

    $_SESSION['auth_errors'] = $errors;
    $_SESSION['old_email'] = $email;
    redirect('/login.php');
}

if ($action === 'register') {
    if (is_logged_in()) {
        redirect('/index.php');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    $errors = [];

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $passwordConfirmation) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $exists = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);

        if ($exists->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $passwordHash, 'student']);

        $userId = (int) $db->lastInsertId();
        $user = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => 'student',
        ];

        login_user($user);
        redirect('/index.php');
    }

    $_SESSION['auth_errors'] = $errors;
    $_SESSION['old_name'] = $name;
    $_SESSION['old_email'] = $email;
    redirect('/register.php');
}

if ($action === 'logout') {
    logout_user();
    redirect('/login.php');
}

http_response_code(400);
exit('Unknown authentication action.');
