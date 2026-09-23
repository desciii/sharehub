<?php
// public/test.php
// Throwaway page to confirm the DB connection + seed data are working.
// Delete this file once you've confirmed it works.

require __DIR__ . '/../includes/config.php';

$subscriptions = $db->query('SELECT * FROM subscriptions')->fetchAll();

echo '<h1>DB Connection Test</h1>';
echo '<pre>';
var_dump($subscriptions);
echo '</pre>';
