<?php
// includes/config.php

$dbPath = __DIR__ . '/../database/sharehub.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec('PRAGMA foreign_keys = ON;');

} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}