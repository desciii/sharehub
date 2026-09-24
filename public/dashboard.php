<?php
// public/dashboard.php — Logged-in user's groups & subscriptions

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/app.php';

require_login();

$user   = current_user();
$userId = current_user_id();

// Every group the user belongs to (owner or member), with subscription info and live slot counts
$stmt = $db->prepare(
    'SELECT
        g.id AS group_id, g.max_members, g.cost_per_slot, g.status, g.created_by,
        s.id AS subscription_id, s.name, s.category, s.icon, s.domain, s.base_price,
        gm.payment_status, gm.joined_at,
        (SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id = g.id) AS members_count
     FROM group_members gm
     JOIN groups g ON g.id = gm.group_id
     JOIN subscriptions s ON s.id = g.subscription_id
     WHERE gm.user_id = ?
     ORDER BY gm.joined_at DESC'
);
$stmt->execute([$userId]);
$myGroups = $stmt->fetchAll();

// Lifetime spend from paid transactions
$stmt = $db->prepare(
    "SELECT COALESCE(SUM(amount + platform_fee), 0) AS total
     FROM transactions
     WHERE user_id = ? AND status = 'paid'"
);
$stmt->execute([$userId]);
$totalSpent = (float) $stmt->fetchColumn();

$activeCount = 0;
$unpaidCount = 0;
foreach ($myGroups as $g) {
    if (in_array($g['status'], ['active', 'full'], true)) $activeCount++;
    if ($g['payment_status'] === 'unpaid') $unpaidCount++;
}

app_header('My subscriptions', 'dashboard');
?>

<style>
  .db-wrap { padding:44px 0 72px; }
  .db-head { margin-bottom:28px; }
  .db-head h1 { font-family:'Newsreader',Georgia,serif; font-weight:500; font-size:clamp(30px,4vw,40px); margin-bottom:6px; }
  .db-head p { color:var(--muted,#6b7570); font-size:15px; }

  .db-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:32px; }
  .db-stat { background:#fff; border:1px solid var(--line,#e6e2da); border-radius:16px; padding:18px 20px; }
  .db-stat span { display:block; font-size:12px; color:var(--muted,#6b7570); text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px; }
  .db-stat b { font-family:'Newsreader',serif; font-size:28px; font-weight:500; }

  .db-empty { background:#fff; border:1px dashed var(--line,#e6e2da); border-radius:16px; padding:48px 24px; text-align:center; color:var(--muted,#6b7570); }
  .db-empty a { color:var(--accent-dk,#16743f); font-weight:600; }

  .db-list { display:flex; flex-direction:column; gap:14px; }
  .db-card { background:#fff; border:1px solid var(--line,#e6e2da); border-radius:16px; padding:18px 20px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; transition:transform .2s, box-shadow .2s; }
  .db-card:hover { transform:translateY(-2px); box-shadow:0 12px 24px rgba(40,50,40,.08); }
  .db-tile { width:52px; height:52px; border-radius:14px; display:grid; place-items:center; font-size:24px; flex:none; background:#fff; border:1px solid var(--line,#e6e2da); box-shadow:0 4px 10px rgba(0,0,0,.06); }
  .db-tile img { width:32px; height:32px; object-fit:contain; }
  .db-info { flex:1; min-width:180px; }
  .db-info h3 { font-size:15.5px; font-weight:600; margin-bottom:2px; }
  .db-info .meta { font-size:12.5px; color:var(--muted,#6b7570); }
  .db-badge { font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:.02em; }
  .db-badge.owner { background:#eaf7ef; color:#16743f; }
  .db-badge.member { background:#e9e5dc; color:#5c5648; }
  .db-badge.paid { background:#eaf7ef; color:#16743f; }
  .db-badge.unpaid { background:#fdf1e2; color:#a35d1f; }
  .db-badge.status-recruiting { background:#e2ecff; color:#2d63d6; }
  .db-badge.status-active, .db-badge.status-full { background:#f1e4ff; color:#7a3fc9; }
  .db-tags { display:flex; gap:6px; flex-wrap:wrap; margin-top:6px; }
  .db-price { text-align:right; min-width:110px; }
  .db-price b { display:block; font-size:16px; color:var(--accent-dk,#16743f); }
  .db-price span { font-size:11.5px; color:var(--muted,#6b7570); }
  .db-actions { flex:none; }
  .db-actions a { display:inline-block; padding:9px 18px; border-radius:999px; font-size:13px; font-weight:500; transition:background .2s, transform .2s; }
  .db-actions a.manage { background:var(--ink,#1d2320); color:#fff; }
  .db-actions a.manage:hover { background:var(--accent-dk,#16743f); }
  .db-actions a.pay { background:var(--accent,#1fa35c); color:#fff; }
  .db-actions a.pay:hover { background:var(--accent-dk,#16743f); }
  .db-actions a.view { background:#e9e5dc; color:var(--ink,#1d2320); }
  .db-actions a.view:hover { background:#ddd8cc; }

  @media (max-width:640px) {
    .db-stats { grid-template-columns:1fr; }
    .db-card { flex-direction:column; align-items:flex-start; }
    .db-price { text-align:left; }
    .db-actions { width:100%; }
    .db-actions a { width:100%; text-align:center; }
  }
</style>

<div class="db-wrap">
  <div class="db-head">
    <h1>Hi, <?= e(explode(' ', trim($user['name']))[0] ?: 'there') ?></h1>
    <p>Here's everything you're sharing right now.</p>
  </div>

  <div class="db-stats">
    <div class="db-stat"><span>Groups you're in</span><b><?= count($myGroups) ?></b></div>
    <div class="db-stat"><span>Active or full</span><b><?= $activeCount ?></b></div>
    <div class="db-stat"><span>Total paid</span><b><?= peso($totalSpent) ?></b></div>
  </div>

  <?php if (!$myGroups): ?>
    <div class="db-empty">
      <p>You haven't joined or created any groups yet.</p>
      <p style="margin-top:8px"><a href="/browse.php">Browse subscriptions</a> or <a href="/create-group.php">create a group</a> to get started.</p>
    </div>
  <?php else: ?>
    <div class="db-list">
      <?php foreach ($myGroups as $g):
          $isOwner   = (int) $g['created_by'] === (int) $userId;
          $slotsLeft = max(0, (int) $g['max_members'] - (int) $g['members_count']);
          $domain    = trim((string) ($g['domain'] ?? ''));
          $fallback  = e($g['icon'] ?: '📦');
      ?>
        <div class="db-card">
          <div class="db-tile">
            <?php if ($domain !== ''): ?>
              <img
                src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128"
                alt="" loading="lazy" width="32" height="32"
                onerror="this.replaceWith(document.createTextNode('<?= $fallback ?>'))"
              >
            <?php else: ?>
              <?= $fallback ?>
            <?php endif; ?>
          </div>

          <div class="db-info">
            <h3><?= e($g['name']) ?></h3>
            <div class="meta"><?= e($g['category']) ?> · <?= (int) $g['members_count'] ?>/<?= (int) $g['max_members'] ?> slots filled<?= $slotsLeft > 0 ? ' · ' . $slotsLeft . ' open' : '' ?></div>
            <div class="db-tags">
              <span class="db-badge <?= $isOwner ? 'owner' : 'member' ?>"><?= $isOwner ? 'Owner' : 'Member' ?></span>
              <span class="db-badge status-<?= e($g['status']) ?>"><?= e(ucfirst($g['status'])) ?></span>
              <span class="db-badge <?= e($g['payment_status']) ?>"><?= e(ucfirst($g['payment_status'])) ?></span>
            </div>
          </div>

          <div class="db-price">
            <b><?= peso((float) $g['cost_per_slot'] + PLATFORM_FEE) ?></b>
            <span>your share + fee</span>
          </div>

          <div class="db-actions">
            <?php if ($isOwner): ?>
              <a href="/edit-group.php?id=<?= (int) $g['group_id'] ?>" class="manage">Manage</a>
            <?php elseif ($g['payment_status'] === 'unpaid'): ?>
              <a href="/checkout.php?group=<?= (int) $g['group_id'] ?>" class="pay">Pay now</a>
            <?php else: ?>
              <a href="/subscription.php?id=<?= (int) $g['subscription_id'] ?>" class="view">View</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php app_footer(); ?>