<?php
// includes/config.php
// Central PDO connection for the whole app. Every other file requires this one.

$dbPath = __DIR__ . '/../database/sharehub.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA foreign_keys = ON;'); // SQLite doesn't enforce FKs unless told to
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Start the session here too, since almost every page needs it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
