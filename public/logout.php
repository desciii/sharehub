<?php
require __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

verify_csrf();
logout_user();

session_start();
$_SESSION['success'] = 'You have been logged out.';

header('Location: /index.php');
exit;
