<?php
// public/browse.php
// Browse subscriptions with search, category, and open-slot filters.

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/app.php';

require_login();

$q     = trim((string) ($_GET['q'] ?? ''));
$cat   = trim((string) ($_GET['category'] ?? ''));
$slots = max(0, (int) ($_GET['slots'] ?? 0));

$categories = $db
    ->query('SELECT DISTINCT category FROM subscriptions ORDER BY category')
    ->fetchAll(PDO::FETCH_COLUMN);

// We intentionally do NOT filter open_slots in SQL — PDO's execute() binds
// scalar params as strings, and SQLite compares INTEGER < TEXT as always
// false. Slots filtering happens in PHP below instead, where the value
// comes back from PDO as a proper int.
$sql = "
    SELECT
        s.id, s.name, s.category, s.icon, s.domain, s.description,
        (SELECT COUNT(*) FROM groups g WHERE g.subscription_id = s.id) AS total_groups,
        (SELECT COUNT(*) FROM groups g
           WHERE g.subscription_id = s.id AND g.status = 'recruiting'
             AND g.max_members > (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id)
        ) AS open_groups,
        (SELECT COALESCE(SUM(g.max_members - (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id)), 0)
           FROM groups g WHERE g.subscription_id = s.id AND g.status = 'recruiting'
        ) AS open_slots,
        (SELECT MIN(g.cost_per_slot) FROM groups g
           WHERE g.subscription_id = s.id AND g.status = 'recruiting'
        ) AS from_price
    FROM subscriptions s
    WHERE 1 = 1
";

$params = [];

if ($q !== '') {
    $sql .= ' AND (s.name LIKE :q1 OR s.description LIKE :q2)';
    $params[':q1'] = $params[':q2'] = '%' . $q . '%';
}
if ($cat !== '') {
    $sql .= ' AND s.category = :cat';
    $params[':cat'] = $cat;
}

$sql .= "
    ORDER BY
        (SELECT COALESCE(SUM(g.max_members - (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id)), 0)
           FROM groups g WHERE g.subscription_id = s.id AND g.status = 'recruiting'
        ) DESC,
        s.name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

if ($slots > 0) {
    $items = array_values(array_filter(
        $items,
        static fn(array $item): bool => (int) $item['open_slots'] >= $slots
    ));
}

$tabUrl = function (string $category) use ($q, $slots): string {
    $params = array_filter([
        'q'        => $q !== '' ? $q : null,
        'category' => $category !== '' ? $category : null,
        'slots'    => $slots > 0 ? $slots : null,
    ]);
    $query = http_build_query($params);
    return '/browse.php' . ($query !== '' ? '?' . $query : '');
};

app_header('Browse subscriptions', 'browse');
?>

<div class="page-head">
    <h1>Browse subscriptions</h1>
    <p>Pick a service, then join an open group or start your own.</p>
</div>

<form class="search" action="/browse.php" method="GET">
    <div class="seg">
        <label for="q">Subscription</label>
        <input id="q" name="q" type="text" value="<?= e($q) ?>" placeholder="Netflix, Canva, Turnitin...">
    </div>
    <div class="seg">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c) ?>" <?= $c === $cat ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="seg">
        <label for="slots">Open slots</label>
        <select id="slots" name="slots">
            <option value="0" <?= $slots === 0 ? 'selected' : '' ?>>Any</option>
            <?php foreach ([1, 2, 3] as $n): ?>
                <option value="<?= $n ?>" <?= $slots === $n ? 'selected' : '' ?>><?= $n ?>+ slot<?= $n > 1 ? 's' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="go" type="submit" aria-label="Search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    </button>
</form>

<div class="tabs">
    <a class="tab<?= $cat === '' ? ' on' : '' ?>" href="<?= e($tabUrl('')) ?>">All</a>
    <?php foreach ($categories as $c): ?>
        <a class="tab<?= $c === $cat ? ' on' : '' ?>" href="<?= e($tabUrl($c)) ?>"><?= e($c) ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$items): ?>
    <div class="empty">
        <h2>Nothing matches that</h2>
        <p>Try a different name, category or fewer open slots.</p>
        <a class="btn" href="/browse.php">Clear filters</a>
    </div>
<?php else: ?>
    <p class="count"><?= count($items) ?> subscription<?= count($items) === 1 ? '' : 's' ?></p>

    <div class="grid">
        <?php foreach ($items as $s):
            $totalGroups = (int) $s['total_groups'];
            $openGroups  = (int) $s['open_groups'];
            $openSlots   = (int) $s['open_slots'];
            $domain      = trim((string) ($s['domain'] ?? ''));
            $fallback    = e($s['icon'] ?: '📦');
        ?>
            <article class="sub <?= e(cat_class($s['category'])) ?>">
                <div class="media">
                    <span class="chip"><?= e($s['category']) ?></span>
                    <span class="logo-tile">
                        <?php if ($domain !== ''): ?>
                            <img
                                src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128"
                                alt="" loading="lazy" width="34" height="34"
                                onerror="this.replaceWith(document.createTextNode('<?= $fallback ?>'))"
                            >
                        <?php else: ?>
                            <?= $fallback ?>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="body">
                    <h3><?= e($s['name']) ?></h3>
                    <p><?= e($s['description']) ?></p>

                    <div class="meta">
                        <?php if ($openGroups > 0): ?>
                            <span class="dot ok"></span><?= $openGroups ?> open group<?= $openGroups === 1 ? '' : 's' ?> · <?= $openSlots ?> slot<?= $openSlots === 1 ? '' : 's' ?> left
                        <?php elseif ($totalGroups > 0): ?>
                            <span class="dot full"></span><?= $totalGroups ?> group<?= $totalGroups === 1 ? '' : 's' ?> created · none open right now
                        <?php else: ?>
                            <span class="dot none"></span>No groups yet
                        <?php endif; ?>
                    </div>

                    <div class="foot">
                        <?php if ($s['from_price'] !== null && $openGroups > 0): ?>
                            <span>From <b><?= e(peso($s['from_price'])) ?></b>/mo</span>
                        <?php elseif ($totalGroups > 0): ?>
                            <span>Full for now</span>
                        <?php else: ?>
                            <span>Be the first</span>
                        <?php endif; ?>
                        <a class="btn" href="/subscription.php?id=<?= (int) $s['id'] ?>">View</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php app_footer(); ?>