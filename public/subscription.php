<?php
// public/subscription.php — One subscription and its groups

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/app.php';

require_login();

$id  = (int) ($_GET['id'] ?? 0);
$uid = (int) current_user_id();

$stmt = $db->prepare('SELECT * FROM subscriptions WHERE id = ?');
$stmt->execute([$id]);
$sub = $stmt->fetch();

if (!$sub) {
    http_response_code(404);
    app_header('Not found', 'browse');
    echo '<div class="empty"><h2>Subscription not found</h2><p>It may have been removed.</p><a class="btn" href="/browse.php">Back to browse</a></div>';
    app_footer();
    exit;
}

$stmt = $db->prepare("
    SELECT g.id, g.max_members, g.cost_per_slot, g.status, g.created_by, u.name AS owner_name,
           (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS members,
           (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id AND gm.user_id = :uid) AS is_member
    FROM groups g
    JOIN users u ON u.id = g.created_by
    WHERE g.subscription_id = :sid
    ORDER BY (g.status = 'recruiting') DESC, g.cost_per_slot ASC, g.id ASC
");
$stmt->execute([':uid' => $uid, ':sid' => $id]);
$groups = $stmt->fetchAll();

app_header($sub['name'], 'browse');
?>
<p class="crumbs"><a href="/browse.php">Browse</a> / <?= e($sub['category']) ?> / <?= e($sub['name']) ?></p>

<section class="detail <?= e(cat_class($sub['category'])) ?>">
  <span class="logo-tile">
    <?php if (!empty($sub['domain'])): ?>
      <img
        src="https://www.google.com/s2/favicons?domain=<?= urlencode($sub['domain']) ?>&sz=128"
        alt="<?= e($sub['name']) ?> logo"
        width="64"
        height="64"
        loading="eager"
      >
    <?php else: ?>
      <?= e($sub['icon'] ?: '📦') ?>
    <?php endif; ?>
  </span>
  <div>
    <span class="chip"><?= e($sub['category']) ?></span>
    <h1><?= e($sub['name']) ?></h1>
    <p><?= e($sub['description']) ?></p>
  </div>
  <a class="btn" href="/create-group.php?subscription=<?= (int) $sub['id'] ?>">Create a group</a>
</section>

<div class="sec-title"><h2>Groups</h2><span class="count" style="margin:0"><?= count($groups) ?> total</span></div>

<?php if (!$groups): ?>
  <div class="empty">
    <h2>No groups yet</h2>
    <p>Have this plan? Start a group and let others share the cost.</p>
    <a class="btn" href="/create-group.php?subscription=<?= (int) $sub['id'] ?>">Create the first group</a>
  </div>
<?php else: ?>
  <div class="groups">
    <?php foreach ($groups as $g):
        $left  = max(0, (int) $g['max_members'] - (int) $g['members']);
        $pct   = $g['max_members'] > 0 ? min(100, round($g['members'] / $g['max_members'] * 100)) : 0;
        $mine  = (int) $g['created_by'] === $uid;
        $isMem = (int) $g['is_member'] > 0;
        $open  = $g['status'] === 'recruiting' && $left > 0;
    ?>
      <div class="grp">
        <div class="g-main"><b>Group by <?= e($g['owner_name']) ?></b><span><?= (int) $g['max_members'] ?> slots</span></div>
        <div class="g-slots">
          <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
          <small><?= (int) $g['members'] ?> of <?= (int) $g['max_members'] ?> joined · <?= $left ?> slot<?= $left === 1 ? '' : 's' ?> left</small>
        </div>
        <div class="g-price"><b><?= e(peso($g['cost_per_slot'])) ?></b><small>/ mo + <?= e(peso(PLATFORM_FEE)) ?> platform fee</small></div>
        <div class="g-act">
          <?php if ($mine): ?>
            <a class="btn line" href="/edit-group.php?id=<?= (int) $g['id'] ?>">Manage</a>
          <?php elseif ($isMem): ?>
            <span class="badge">You're in</span>
          <?php elseif ($open): ?>
            <a class="btn" href="/checkout.php?group=<?= (int) $g['id'] ?>">Join</a>
          <?php else: ?>
            <span class="badge muted"><?= $left === 0 ? 'Full' : e(ucfirst($g['status'])) ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php app_footer(); ?>