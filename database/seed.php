<?php
// database/seed.php
// Run AFTER init.php to populate sample data.
// Usage: php database/seed.php   (or open in browser)
// Safe to re-run: existing rows are matched by email/name and updated in place.

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

// --- Real subscription catalog (matches the landing page) ---
// [name, domain, category, fallback emoji, base_price (₱/mo), description]
$subscriptions = [
    ['Netflix', 'netflix.com', 'Entertainment', '🎬', 549, 'Streaming service for movies, series and original shows you can watch on any device.'],
    ['Crunchyroll', 'crunchyroll.com', 'Entertainment', '🍥', 299, 'Anime streaming with a huge library, including new episodes right after they air in Japan.'],
    ['Spotify Premium', 'spotify.com', 'Entertainment', '🎵', 149, 'Ad-free music and podcasts with offline downloads and unlimited skips.'],
    ['Disney+', 'disneyplus.com', 'Entertainment', '🏰', 299, 'Streaming home of Disney, Pixar, Marvel, Star Wars and National Geographic.'],
    ['Turnitin', 'turnitin.com', 'Education', '📄', 150, 'Plagiarism checker that schools use to review how original a paper is.'],
    ['Grammarly', 'grammarly.com', 'Education', '✍️', 690, 'Writing assistant that fixes grammar, spelling, clarity and tone as you type.'],
    ['Quizlet Plus', 'quizlet.com', 'Education', '🧠', 460, 'Flashcards and study modes for exams, with no ads and extra practice tests.'],
    ['Coursera Plus', 'coursera.org', 'Education', '🎓', 1699, 'Unlimited access to thousands of online courses and certificates from top universities.'],
    ['Canva Pro', 'canva.com', 'Creative', '🎨', 549, 'Design tool with premium templates, a background remover and brand kits.'],
    ['Adobe Creative Cloud', 'adobe.com', 'Creative', '🖌️', 2999, 'Photoshop, Illustrator, Premiere Pro and more of the industry-standard creative apps.'],
    ['CapCut Pro', 'capcut.com', 'Creative', '✂️', 399, 'Video editor with premium effects, templates and exports without a watermark.'],
    ['Microsoft 365', 'microsoft.com', 'Productivity', '💻', 499, 'Word, Excel, PowerPoint and Teams, plus 1 TB of OneDrive cloud storage.'],
    ['Notion Plus', 'notion.so', 'Productivity', '📝', 575, 'Notes, docs and project planning together in one organized workspace.'],
    ['ChatGPT Plus', 'chatgpt.com', 'Productivity', '🤖', 1150, 'AI assistant for writing, coding and study help, with priority access to newer models.'],
    ['Perplexity Pro', 'perplexity.ai', 'Research', '🔬', 1150, 'AI research assistant that answers your questions with cited sources.'],
    ['Wolfram Alpha Pro', 'wolframalpha.com', 'Research', '🧮', 420, 'Step-by-step solutions for math, science and data questions.'],
];

$subIds = [];
foreach ($subscriptions as [$name, $domain, $cat, $icon, $basePrice, $desc]) {
    $exists = $db->prepare('SELECT id FROM subscriptions WHERE name = ?');
    $exists->execute([$name]);
    $row = $exists->fetch();

    if ($row) {
        $subIds[$name] = $row['id'];
        // Backfill domain/icon/price/desc on existing rows (e.g. old generic catalog).
        $update = $db->prepare(
            'UPDATE subscriptions SET category = ?, icon = ?, domain = ?, base_price = ?, description = ? WHERE id = ?'
        );
        $update->execute([$cat, $icon, $domain, $basePrice, $desc, $row['id']]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO subscriptions (name, category, icon, domain, base_price, description) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $cat, $icon, $domain, $basePrice, $desc]);
        $subIds[$name] = $db->lastInsertId();
    }
}

// --- Sample groups (needs at least one user to be "created_by") ---
$creator = $db->query("SELECT id FROM users WHERE email = 'karyl@student.com'")->fetch();
$creatorId = $creator['id'];

$groups = [
    ['Canva Pro', 5, 80],
    ['Microsoft 365', 6, 225],
    ['Spotify Premium', 6, 75],
];

foreach ($groups as [$subName, $maxMembers, $costPerSlot]) {
    if (!isset($subIds[$subName])) {
        continue; // subscription name changed/removed from catalog above
    }

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