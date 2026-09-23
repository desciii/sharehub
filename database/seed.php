<?php
// database/seed.php
// Run AFTER init.php to populate sample data.
// Usage: php database/seed.php   (or open in browser)

require __DIR__ . '/../includes/config.php';

// --- Sample users ---
$users = [
    ['Karyl Student', 'karyl@student.com', 'password123', 'student'],
    ['Admin User', 'admin@sharehub.com', 'admin123', 'admin'],
];

foreach ($users as [$name, $email, $plainPassword, $role]) {
    $exists = $db->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if (!$exists->fetch()) {
        $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($plainPassword, PASSWORD_DEFAULT), $role]);
    }
}

// --- Sample subscriptions (matches your prototype) ---
$subscriptions = [
    ['Canva Pro', 'Creative', '🎨', 600, 'Creative and design tools for student projects.'],
    ['Microsoft 365', 'Productivity', '💻', 1350, 'Productivity and document tools for schoolwork and collaboration.'],
    ['Spotify Premium', 'Entertainment', '🎵', 450, 'Entertainment subscription example for an eligible group plan.'],
    ['Notion Plus', 'Productivity', '📝', 450, 'Workspace and organization tools for notes and projects.'],
    ['Research Tools', 'Research', '📊', 800, 'Prototype research/data subscription listing for student research teams.'],
    ['Education Platform', 'Education', '📚', 700, 'Online learning resources organized into an eligible group plan.'],
];

$subIds = [];
foreach ($subscriptions as [$name, $cat, $icon, $basePrice, $desc]) {
    $exists = $db->prepare('SELECT id FROM subscriptions WHERE name = ?');
    $exists->execute([$name]);
    $row = $exists->fetch();
    if ($row) {
        $subIds[$name] = $row['id'];
    } else {
        $stmt = $db->prepare('INSERT INTO subscriptions (name, category, icon, base_price, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $cat, $icon, $basePrice, $desc]);
        $subIds[$name] = $db->lastInsertId();
    }
}

// --- Sample groups (needs at least one user to be "created_by") ---
$creator = $db->query("SELECT id FROM users WHERE email = 'karyl@student.com'")->fetch();
$creatorId = $creator['id'];

$groups = [
    ['Canva Pro', 5, 120],
    ['Microsoft 365', 6, 225],
    ['Spotify Premium', 6, 75],
];

foreach ($groups as [$subName, $maxMembers, $costPerSlot]) {
    $exists = $db->prepare('SELECT id FROM groups WHERE subscription_id = ?');
    $exists->execute([$subIds[$subName]]);
    if (!$exists->fetch()) {
        $stmt = $db->prepare('INSERT INTO groups (subscription_id, created_by, max_members, cost_per_slot, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$subIds[$subName], $creatorId, $maxMembers, $costPerSlot, 'recruiting']);

        $groupId = $db->lastInsertId();
        $db->prepare('INSERT INTO group_members (group_id, user_id, payment_status) VALUES (?, ?, ?)')
           ->execute([$groupId, $creatorId, 'paid']);
    }
}

echo "Seed data inserted." . PHP_EOL;
