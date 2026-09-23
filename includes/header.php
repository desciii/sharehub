<?php
// includes/header.php
// Include this at the top of every public page (after config.php).
// Expects an optional $pageTitle variable to be set before including.
$pageTitle = $pageTitle ?? 'ShareHub';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — ShareHub</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app">
