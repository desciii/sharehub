<?php
// database/init.php
// Run this ONCE to create sharehub.sqlite from schema.sql.
// Usage: php database/init.php   (or open in browser)

$dbPath = __DIR__ . '/sharehub.sqlite';
$schemaPath = __DIR__ . '/schema.sql';

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents($schemaPath);
$db->exec($sql);

echo "Database created at $dbPath" . PHP_EOL;
