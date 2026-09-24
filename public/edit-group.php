<?php
// public/edit-group.php — Owner edits slots and price of their group

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/app.php';

require_login();

$userId  = (int) current_user_id();
$groupId = (int) ($_GET['id'] ?? $_POST['group_id'] ?? 0);

$stmt = $db->prepare(
    'SELECT
        g.id, g.max_members, g.cost_per_slot, g.status, g.created_by,
        s.id AS subscription_id, s.name, s.category, s.icon, s.domain, s.base_price,
        (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS members_count
     FROM groups g
     JOIN subscriptions s ON s.id = g.subscription_id
     WHERE g.id = ?'
);
$stmt->execute([$groupId]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

// Only the owner can edit
if (!$group || (int) $group['created_by'] !== $userId) {
    redirect('/dashboard.php');
}

$members = (int) $group['members_count'];
$errors  = [];
$old = [
    'max_members'   => (string) $group['max_members'],
    'cost_per_slot' => (string) (float) $group['cost_per_slot'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['max_members']   = (string) ($_POST['max_members'] ?? '');
    $old['cost_per_slot'] = (string) ($_POST['cost_per_slot'] ?? '');

    $maxMembers  = filter_var($old['max_members'], FILTER_VALIDATE_INT);
    $costPerSlot = filter_var($old['cost_per_slot'], FILTER_VALIDATE_FLOAT);

    if ($maxMembers === false || $maxMembers < 2 || $maxMembers > 20) {
        $errors[] = 'Slots must be a number between 2 and 20.';
    } elseif ($maxMembers < $members) {
        $errors[] = "You already have $members members, so slots can't go below that.";
    }

    if ($costPerSlot === false || $costPerSlot <= 0) {
        $errors[] = 'Enter a valid price per slot.';
    } elseif ($costPerSlot > 100000) {
        $errors[] = 'That price looks too high — double check it.';
    }

    if (!$errors) {
        // Keep the status in step with the slot count
        $status = $group['status'];
        if ($members >= $maxMembers && $status === 'recruiting') {
            $status = 'full';
        } elseif ($members < $maxMembers && $status === 'full') {
            $status = 'recruiting';
        }

        try {
            $upd = $db->prepare(
                'UPDATE groups SET max_members = ?, cost_per_slot = ?, status = ?
                 WHERE id = ? AND created_by = ?'
            );
            $upd->execute([$maxMembers, $costPerSlot, $status, $groupId, $userId]);

            redirect('/edit-group.php?id=' . $groupId . '&saved=1');
        } catch (Throwable $e) {
            $errors[] = 'Something went wrong saving your changes. Please try again.';
        }
    }
}

// Turbo only renders a form response with errors if it isn't a plain 200
if ($errors) {
    http_response_code(422);
}

$saved    = isset($_GET['saved']) && $_SERVER['REQUEST_METHOD'] !== 'POST';
$domain   = trim((string) ($group['domain'] ?? ''));
$fallback = e($group['icon'] ?: '📦');

app_header('Manage group', 'dashboard');
?>

<style>
  .eg-wrap { width:min(680px,100%); margin:0 auto; padding:48px 0 72px; }
  .eg-head { text-align:center; margin-bottom:32px; }
  .eg-head h1 { font-family:'Newsreader',Georgia,serif; font-weight:500; font-size:clamp(30px,4vw,40px); margin-bottom:8px; }
  .eg-head p { color:var(--muted,#6b7570); font-size:15px; }

  .eg-card { width:100%; background:#fff; border:1px solid var(--line,#e6e2da); border-radius:20px; padding:clamp(24px,4vw,40px); box-shadow:0 10px 30px rgba(40,50,40,.06); }

  .eg-alert { background:#fdecec; color:#a02b2b; border-radius:14px; padding:12px 16px; margin-bottom:20px; font-size:14px; }
  .eg-ok { background:#eaf7ef; color:#16743f; border-radius:14px; padding:12px 16px; margin-bottom:20px; font-size:14px; }

  .eg-sub { display:flex; align-items:center; gap:12px; background:#f7f5f0; border-radius:14px; padding:14px 16px; margin-bottom:22px; }
  .eg-sub .tile { width:44px; height:44px; border-radius:12px; background:#fff; display:grid; place-items:center; font-size:20px; box-shadow:0 4px 10px rgba(0,0,0,.08); flex:none; }
  .eg-sub .tile img { width:26px; height:26px; object-fit:contain; }
  .eg-sub b { display:block; font-size:14px; }
  .eg-sub span.m { font-size:12.5px; color:var(--muted,#6b7570); }

  .eg-field { margin-bottom:18px; }
  .eg-field label { display:block; font-size:12px; font-weight:600; color:var(--muted,#6b7570); margin:0 0 6px 4px; text-transform:uppercase; letter-spacing:.03em; }
  .eg-field input { width:100%; padding:13px 18px; border:1.5px solid var(--line,#e6e2da); border-radius:14px; font:inherit; font-size:15px; color:var(--ink,#1d2320); background:#fff; transition:border-color .2s, box-shadow .2s; }
  .eg-field input:hover { border-color:#c9cfd6; }
  .eg-field input:focus { outline:none; border-color:var(--accent,#1fa35c); box-shadow:0 0 0 4px rgba(31,163,92,.12); }
  .eg-hint { display:block; margin:6px 0 0 4px; font-size:12.5px; color:var(--muted,#6b7570); }

  .eg-actions { display:flex; gap:10px; margin-top:24px; }
  .eg-actions .btn { flex:1; justify-content:center; padding:14px 28px; border:0; cursor:pointer; font-family:inherit; }
  .eg-cancel { display:inline-flex; align-items:center; justify-content:center; padding:14px 26px; border:1.5px solid var(--ink,#1d2320); border-radius:999px; font-weight:500; font-size:15px; transition:background .25s, color .25s; }
  .eg-cancel:hover { background:var(--ink,#1d2320); color:#fff; }
</style>

<div class="eg-wrap">

  <div class="eg-head">
    <h1>Manage group</h1>
    <p>Change the slots or the price for your <?= e($group['name']) ?> group.</p>
  </div>

  <div class="eg-card">
    <?php if ($saved): ?>
      <div class="eg-ok" role="status">Changes saved.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="eg-alert" role="alert"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <div class="eg-sub">
      <span class="tile">
        <?php if ($domain !== ''): ?>
          <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128" alt="" width="26" height="26"
               onerror="this.replaceWith(document.createTextNode('<?= $fallback ?>'))">
        <?php else: ?><?= $fallback ?><?php endif; ?>
      </span>
      <div>
        <b><?= e($group['name']) ?></b>
        <span class="m"><?= e($group['category']) ?> · <?= $members ?> member<?= $members === 1 ? '' : 's' ?> so far · <?= e(ucfirst($group['status'])) ?><?= (float) $group['base_price'] > 0 ? ' · Listed at ' . e(peso($group['base_price'])) : '' ?></span>
      </div>
    </div>

    <form method="POST" action="/edit-group.php?id=<?= $groupId ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="group_id" value="<?= $groupId ?>">

      <div class="eg-field">
        <label for="max_members">Slots (including you)</label>
        <input id="max_members" name="max_members" type="number" min="<?= max(2, $members) ?>" max="20" step="1" required value="<?= e($old['max_members']) ?>">
        <span class="eg-hint">Between <?= max(2, $members) ?> and 20. Can't go below your current member count.</span>
      </div>

      <div class="eg-field">
        <label for="cost_per_slot">Price per slot (₱)</label>
        <input id="cost_per_slot" name="cost_per_slot" type="number" min="1" step="0.01" required value="<?= e($old['cost_per_slot']) ?>">
        <span class="eg-hint">Members also pay a <?= e(peso(PLATFORM_FEE)) ?> platform fee at checkout. New price applies to people who join after you save.</span>
      </div>

      <div class="eg-actions">
        <a class="eg-cancel" href="/dashboard.php">Back</a>
        <button type="submit" class="btn" data-turbo-submits-with="Saving…">Save changes</button>
      </div>
    </form>
  </div>
</div>

<?php app_footer(); ?>