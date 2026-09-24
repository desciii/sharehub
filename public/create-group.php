<?php
// public/create-group.php — Create a subscription group

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/app.php';

require_login();

$userId = current_user_id();

// Subscriptions to pick from
$subscriptions = $db->query(
    'SELECT id, name, category, icon, domain, base_price, description
     FROM subscriptions
     ORDER BY category, name'
)->fetchAll();

// Preselect via ?subscription=ID
$preselect = (int) ($_GET['subscription'] ?? 0);

$errors = [];
$old = [
    'subscription_id' => $preselect ?: '',
    'max_members'     => '',
    'cost_per_slot'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['subscription_id'] = $_POST['subscription_id'] ?? '';
    $old['max_members']     = $_POST['max_members'] ?? '';
    $old['cost_per_slot']   = $_POST['cost_per_slot'] ?? '';

    $subscriptionId = (int) $old['subscription_id'];
    $maxMembers     = filter_var($old['max_members'], FILTER_VALIDATE_INT);
    $costPerSlot    = filter_var($old['cost_per_slot'], FILTER_VALIDATE_FLOAT);

    // Subscription must exist
    $subscription = null;
    if ($subscriptionId > 0) {
        $stmt = $db->prepare('SELECT id, name, base_price FROM subscriptions WHERE id = ?');
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch();
    }
    if (!$subscription) {
        $errors[] = 'Please choose a valid subscription.';
    }

    if ($maxMembers === false || $maxMembers < 2 || $maxMembers > 20) {
        $errors[] = 'Slots must be a number between 2 and 20.';
    }

    if ($costPerSlot === false || $costPerSlot <= 0) {
        $errors[] = 'Enter a valid price per slot.';
    } elseif ($costPerSlot > 100000) {
        $errors[] = 'That price looks too high — double check it.';
    }

    if (!$errors) {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                'INSERT INTO groups (subscription_id, created_by, max_members, cost_per_slot, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $subscriptionId,
                $userId,
                $maxMembers,
                $costPerSlot,
                'recruiting',
                date('Y-m-d H:i:s'),
            ]);
            $groupId = (int) $db->lastInsertId();

            $stmt = $db->prepare(
                'INSERT INTO group_members (group_id, user_id, payment_status, joined_at)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$groupId, $userId, 'unpaid', date('Y-m-d H:i:s')]);

            $db->commit();

            redirect('/subscription.php?id=' . $subscriptionId);
        } catch (Throwable $e) {
            $db->rollBack();
            $errors[] = 'Something went wrong creating the group. Please try again.';
        }
    }
}

// Turbo only renders a form response with errors if it isn't a plain 200
if ($errors) {
    http_response_code(422);
}

app_header('Create a group', 'create');
?>

<style>
  .cg-wrap { width:min(680px,100%); margin:0 auto; padding:48px 0 72px; }
  .cg-head { text-align:center; margin-bottom:32px; }
  .cg-head h1 { font-family:'Newsreader',Georgia,serif; font-weight:500; font-size:clamp(30px,4vw,40px); margin-bottom:8px; }
  .cg-head p { color:var(--muted,#6b7570); font-size:15px; }

  .cg-card { width:100%; background:#fff; border:1px solid var(--line,#e6e2da); border-radius:20px; padding:clamp(24px,4vw,40px); box-shadow:0 10px 30px rgba(40,50,40,.06); }

  .cg-alert { background:#fdecec; color:#a02b2b; border-radius:14px; padding:12px 16px; margin-bottom:20px; font-size:14px; }
  .cg-alert ul { list-style:none; padding-left:0; }
  .cg-alert li + li { margin-top:3px; }

  .cg-field { margin-bottom:18px; }
  .cg-field label { display:block; font-size:12px; font-weight:600; color:var(--muted,#6b7570); margin:0 0 6px 4px; text-transform:uppercase; letter-spacing:.03em; }
  .cg-field select,
  .cg-field input { width:100%; padding:13px 18px; border:1.5px solid var(--line,#e6e2da); border-radius:14px; font:inherit; font-size:15px; color:var(--ink,#1d2320); background:#fff; transition:border-color .2s, box-shadow .2s; }
  .cg-field select:hover, .cg-field input:hover { border-color:#c9cfd6; }
  .cg-field select:focus, .cg-field input:focus { outline:none; border-color:var(--accent,#1fa35c); box-shadow:0 0 0 4px rgba(31,163,92,.12); }
  .cg-hint { display:block; margin:6px 0 0 4px; font-size:12.5px; color:var(--muted,#6b7570); }

  .cg-preview { display:flex; align-items:center; gap:12px; background:#f7f5f0; border-radius:14px; padding:14px 16px; margin-bottom:20px; }
  .cg-preview .tile { width:44px; height:44px; border-radius:12px; background:#fff; display:grid; place-items:center; font-size:20px; box-shadow:0 4px 10px rgba(0,0,0,.08); flex:none; }
  .cg-preview .tile img { width:26px; height:26px; object-fit:contain; }
  .cg-preview .info b { display:block; font-size:14px; }
  .cg-preview .info span { font-size:12.5px; color:var(--muted,#6b7570); }

  .cg-estimate { background:#eaf7ef; border-radius:14px; padding:14px 16px; margin-bottom:24px; font-size:13.5px; color:#16743f; display:none; }
  .cg-estimate.show { display:block; }
  .cg-estimate b { font-size:15px; }

  .cg-submit { width:100%; justify-content:center; padding:14px 28px; border:0; cursor:pointer; font-family:inherit; }
</style>

<div class="cg-wrap">

  <div class="cg-head">
    <h1>Create a group</h1>
    <p>Set your slots and price — you'll take one slot yourself.</p>
  </div>

  <div class="cg-card">
    <?php if ($errors): ?>
      <div class="cg-alert" role="alert">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!$subscriptions): ?>
      <p style="color:var(--muted,#6b7570)">No subscriptions are available yet.</p>
    <?php else: ?>
      <form method="POST" action="/create-group.php" id="cgForm">
        <?= csrf_field() ?>

        <div class="cg-field">
          <label for="subscription_id">Subscription</label>
          <select id="subscription_id" name="subscription_id" required>
            <option value="">Choose a subscription…</option>
            <?php foreach ($subscriptions as $s): ?>
              <option
                value="<?= (int) $s['id'] ?>"
                data-base-price="<?= (float) $s['base_price'] ?>"
                data-icon="<?= e($s['icon']) ?>"
                data-domain="<?= e($s['domain'] ?? '') ?>"
                <?= (string) $old['subscription_id'] === (string) $s['id'] ? 'selected' : '' ?>
              ><?= e($s['name']) ?> — <?= e($s['category']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="cg-preview" id="cgPreview" style="display:none">
          <span class="tile" id="cgTile">📦</span>
          <div class="info">
            <b id="cgName"></b>
            <span id="cgBase"></span>
          </div>
        </div>

        <div class="cg-field">
          <label for="max_members">Slots (including you)</label>
          <input
            id="max_members" name="max_members" type="number"
            min="2" max="20" step="1" required
            value="<?= e((string) $old['max_members']) ?>"
            placeholder="e.g. 4"
          >
          <span class="cg-hint">Between 2 and 20 members.</span>
        </div>

        <div class="cg-field">
          <label for="cost_per_slot">Price per slot (₱)</label>
          <input
            id="cost_per_slot" name="cost_per_slot" type="number"
            min="1" step="0.01" required
            value="<?= e((string) $old['cost_per_slot']) ?>"
            placeholder="e.g. 75"
          >
          <span class="cg-hint">Members also pay a small platform fee (<?= peso(PLATFORM_FEE) ?>) at checkout.</span>
        </div>

        <div class="cg-estimate" id="cgEstimate">
          At this price, the group covers about <b id="cgTotal"></b> of the plan's <span id="cgListedBase"></span> price.
        </div>

        <button type="submit" class="btn cg-submit">Create group</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  const sel       = document.getElementById('subscription_id');
  if (!sel) return; // no subscriptions -> no form

  const preview   = document.getElementById('cgPreview');
  const tile      = document.getElementById('cgTile');
  const nameEl    = document.getElementById('cgName');
  const baseEl    = document.getElementById('cgBase');
  const membersEl = document.getElementById('max_members');
  const priceEl   = document.getElementById('cost_per_slot');
  const estBox    = document.getElementById('cgEstimate');
  const totalEl   = document.getElementById('cgTotal');
  const listedEl  = document.getElementById('cgListedBase');

  function pesoFmt(n) {
    return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updatePreview() {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) { preview.style.display = 'none'; estBox.classList.remove('show'); return; }
    const domain = opt.dataset.domain || '';

    if (domain) {
      tile.innerHTML = '<img src="https://www.google.com/s2/favicons?domain=' +
        encodeURIComponent(domain) + '&sz=128" alt="" width="26" height="26">';
    } else {
      tile.textContent = opt.dataset.icon || '📦';
    }
    nameEl.textContent = opt.text;
    const base = parseFloat(opt.dataset.basePrice || '0');
    baseEl.textContent = base ? 'Listed at ' + pesoFmt(base) : '';
    preview.style.display = 'flex';
    updateEstimate(base);
  }

  function updateEstimate(base) {
    const members = parseInt(membersEl.value, 10);
    const price = parseFloat(priceEl.value);
    if (!base || !members || !price) { estBox.classList.remove('show'); return; }
    totalEl.textContent = pesoFmt(members * price);
    listedEl.textContent = pesoFmt(base);
    estBox.classList.add('show');
  }

  sel.addEventListener('change', updatePreview);
  [membersEl, priceEl].forEach(el => el.addEventListener('input', () => {
    const opt = sel.options[sel.selectedIndex];
    updateEstimate(opt ? parseFloat(opt.dataset.basePrice || '0') : 0);
  }));

  updatePreview();
})();
</script>

<?php app_footer(); ?>