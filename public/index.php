<?php
// public/index.php — Homepage

require __DIR__ . '/../includes/config.php';

$pageTitle = 'Home';
$activePage = 'home';

// Featured subscriptions: top 3, with slot counts calculated from group_members
$stmt = $db->query("
    SELECT s.id, s.name, s.category, s.icon, s.description,
           g.id AS group_id, g.cost_per_slot, g.max_members,
           (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS current_members
    FROM subscriptions s
    JOIN groups g ON g.subscription_id = s.id
    ORDER BY s.id
    LIMIT 3
");
$featured = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<main class="main">
  <header class="topbar">
    <button class="mobile-menu">☰</button>
    <div class="search">⌕ <input placeholder="Search subscriptions..."></div>
    <div style="font-size:14px"><b>🔔</b> &nbsp; <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></div>
  </header>

  <div class="content">
    <section class="page active">
      <div class="hero">
        <div>
          <div style="font-weight:700;opacity:.85">MICRO-SUBSCRIPTION POOLING</div>
          <h1>Share subscriptions.<br>Save more.</h1>
          <p>Find eligible group plans for education, entertainment, creative tools, productivity, and research. Share costs with other students through organized subscription groups.</p>
          <a href="/browse.php"><button class="btn btn-light">Browse subscriptions →</button></a>
          <a href="/create-group.php"><button class="btn" style="background:rgba(255,255,255,.16);color:#fff;margin-left:7px">Create a group</button></a>
        </div>
        <div class="hero-stat"><span>Estimated community savings</span><strong>₱24,850</strong><span>this month</span></div>
      </div>

      <div class="section-head">
        <h2>Popular subscriptions</h2>
        <a href="/browse.php"><button class="btn btn-ghost">View all</button></a>
      </div>

      <div class="grid">
        <?php foreach ($featured as $s): ?>
          <?php $pct = $s['max_members'] > 0 ? round($s['current_members'] / $s['max_members'] * 100) : 0; ?>
          <div class="card">
            <div class="sub-icon"><?= htmlspecialchars($s['icon']) ?></div>
            <div class="row">
              <div>
                <h3><?= htmlspecialchars($s['name']) ?></h3>
                <span class="muted"><?= htmlspecialchars($s['category']) ?></span>
              </div>
              <span class="badge"><?= $s['current_members'] < $s['max_members'] ? 'Open' : 'Full' ?></span>
            </div>
            <div class="price">₱<?= number_format($s['cost_per_slot'], 0) ?><span class="muted"> / month</span></div>
            <span class="muted"><?= $s['max_members'] - $s['current_members'] ?> slot(s) available</span>
            <div class="progress"><span style="width:<?= $pct ?>%"></span></div>
            <div class="row">
              <span class="muted"><?= $s['current_members'] ?>/<?= $s['max_members'] ?> members</span>
              <a href="/subscription.php?id=<?= $s['id'] ?>"><button class="btn btn-primary">View & join</button></a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
