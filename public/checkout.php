<?php
// public/checkout.php — Pay your share of a group (payment is simulated)

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/app.php';

require_login();

$userId  = current_user_id();
$groupId = (int) ($_GET['group'] ?? $_POST['group_id'] ?? 0);

// The group + subscription (anyone logged in can open this to join)
$stmt = $db->prepare(
    'SELECT
        g.id AS group_id, g.max_members, g.cost_per_slot, g.status, g.created_by,
        s.id AS subscription_id, s.name, s.category, s.icon, s.domain, s.description,
        (SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id = g.id) AS members_count
     FROM groups g
     JOIN subscriptions s ON s.id = g.subscription_id
     WHERE g.id = ?'
);
$stmt->execute([$groupId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('/dashboard.php');
}

// Is this user already in the group?
$stmt = $db->prepare('SELECT payment_status FROM group_members WHERE group_id = ? AND user_id = ?');
$stmt->execute([$groupId, $userId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);
$isMember   = (bool) $membership;
$order['payment_status'] = $membership['payment_status'] ?? null;

// Not a member and can't join (full / closed) -> back to the subscription page
$joinable = $order['status'] === 'recruiting' && (int) $order['members_count'] < (int) $order['max_members'];
if (!$isMember && !$joinable) {
    redirect('/subscription.php?id=' . (int) $order['subscription_id']);
}

$cost   = (float) $order['cost_per_slot'];
$fee    = (float) PLATFORM_FEE;
$total  = $cost + $fee;
$isPaid = $order['payment_status'] === 'paid';
$justPaid = $isPaid && isset($_GET['done']);

$methods = [
    'gcash' => ['GCash',        'Pay with your GCash wallet'],
    'maya'  => ['Maya',         'Pay with your Maya wallet'],
    'card'  => ['Card',         'Debit or credit card'],
];

$errors = [];
$method = 'gcash';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isPaid) {
    verify_csrf();

    $method = (string) ($_POST['method'] ?? '');
    if (!isset($methods[$method])) {
        $errors[] = 'Please choose a payment method.';
        $method = 'gcash';
    }

    if (!$errors) {
        try {
            $db->beginTransaction();

            // Joining: re-check the slot is still free (someone may have taken it)
            if (!$isMember) {
                $cnt = $db->prepare('SELECT COUNT(*) FROM group_members WHERE group_id = ?');
                $cnt->execute([$groupId]);
                if ($order['status'] !== 'recruiting' || (int) $cnt->fetchColumn() >= (int) $order['max_members']) {
                    throw new RuntimeException('full');
                }
            }

            // Only insert into columns that actually exist in your transactions table
            $cols = array_column($db->query('PRAGMA table_info(transactions)')->fetchAll(PDO::FETCH_ASSOC), 'name');
            $now  = date('Y-m-d H:i:s');
            $candidate = [
                'user_id'         => $userId,
                'group_id'        => $groupId,
                'subscription_id' => (int) $order['subscription_id'],
                'amount'          => $cost,
                'platform_fee'    => $fee,
                'status'          => 'paid',
                'payment_method'  => $method,
                'method'          => $method,
                'created_at'      => $now,
                'paid_at'         => $now,
            ];
            $data = array_intersect_key($candidate, array_flip($cols));

            if (!isset($data['user_id'], $data['amount'])) {
                throw new RuntimeException('transactions table is missing user_id/amount');
            }

            $sql = 'INSERT INTO transactions (' . implode(', ', array_keys($data)) . ')
                    VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')';
            $db->prepare($sql)->execute(array_values($data));

            if ($isMember) {
                $upd = $db->prepare(
                    "UPDATE group_members SET payment_status = 'paid'
                     WHERE group_id = ? AND user_id = ? AND payment_status <> 'paid'"
                );
                $upd->execute([$groupId, $userId]);
            } else {
                $ins = $db->prepare(
                    'INSERT INTO group_members (group_id, user_id, payment_status, joined_at)
                     VALUES (?, ?, ?, ?)'
                );
                $ins->execute([$groupId, $userId, 'paid', $now]);
            }

            $db->commit();

            redirect('/checkout.php?group=' . $groupId . '&done=1');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = $e->getMessage() === 'full'
                ? 'Sorry, that group just filled up.'
                : 'Payment could not be processed. Please try again.';
        }
    }
}

// Turbo only renders a form response with errors if it isn't a plain 200
if ($errors) {
    http_response_code(422);
}

$domain   = trim((string) ($order['domain'] ?? ''));
$fallback = e($order['icon'] ?: '📦');
$slotsLeft = max(0, (int) $order['max_members'] - (int) $order['members_count']);

app_header($isPaid ? 'Payment complete' : 'Checkout', 'dashboard');
?>

<style>
  .co-wrap { padding:44px 0 72px; }
  .co-head { margin-bottom:28px; }
  .co-head h1 { font-family:'Newsreader',Georgia,serif; font-weight:500; font-size:clamp(30px,4vw,40px); margin-bottom:6px; }
  .co-head p { color:var(--muted,#6b7570); font-size:15px; }

  .co-grid { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr); gap:20px; align-items:start; }
  .co-card { background:#fff; border:1px solid var(--line,#e6e2da); border-radius:20px; padding:clamp(22px,3vw,32px); box-shadow:0 10px 30px rgba(40,50,40,.06); }
  .co-card h2 { font-family:'Inter',sans-serif; font-size:12px; font-weight:600; color:var(--muted,#6b7570); text-transform:uppercase; letter-spacing:.03em; margin-bottom:16px; }

  .co-alert { background:#fdecec; color:#a02b2b; border-radius:14px; padding:12px 16px; margin-bottom:18px; font-size:14px; }

  /* Methods */
  .co-methods { display:flex; flex-direction:column; gap:10px; margin-bottom:22px; }
  .co-method { position:relative; display:flex; align-items:center; gap:14px; padding:14px 16px; border:1.5px solid var(--line,#e6e2da); border-radius:14px; cursor:pointer; background:#fff; transition:border-color .2s, box-shadow .2s, background .2s; }
  .co-method:hover { border-color:#c9cfd6; }
  .co-method input { position:absolute; opacity:0; pointer-events:none; }
  .co-method .dot { width:18px; height:18px; border-radius:50%; border:2px solid #c9cfd6; flex:none; display:grid; place-items:center; transition:border-color .2s; }
  .co-method .dot::after { content:""; width:8px; height:8px; border-radius:50%; background:var(--accent,#1fa35c); transform:scale(0); transition:transform .2s; }
  .co-method b { display:block; font-size:14.5px; }
  .co-method span.d { font-size:12.5px; color:var(--muted,#6b7570); }
  .co-method:has(input:checked) { border-color:var(--accent,#1fa35c); background:#f4fbf7; box-shadow:0 0 0 4px rgba(31,163,92,.1); }
  .co-method:has(input:checked) .dot { border-color:var(--accent,#1fa35c); }
  .co-method:has(input:checked) .dot::after { transform:scale(1); }
  .co-method:has(input:focus-visible) { outline:3px solid var(--accent,#1fa35c); outline-offset:2px; }

  .co-pay { width:100%; justify-content:center; padding:14px 28px; border:0; cursor:pointer; font-family:inherit; }
  .co-note { margin-top:12px; font-size:12.5px; color:var(--muted,#6b7570); text-align:center; }

  /* Summary */
  .co-item { display:flex; align-items:center; gap:14px; padding-bottom:18px; margin-bottom:16px; border-bottom:1px dashed var(--line,#e6e2da); }
  .co-tile { width:52px; height:52px; border-radius:14px; background:#f7f5f0; display:grid; place-items:center; font-size:24px; flex:none; box-shadow:0 4px 10px rgba(0,0,0,.06); }
  .co-tile img { width:30px; height:30px; object-fit:contain; }
  .co-item h3 { font-size:16px; font-weight:600; margin-bottom:2px; }
  .co-item .meta { font-size:12.5px; color:var(--muted,#6b7570); }
  .co-line { display:flex; justify-content:space-between; font-size:14px; padding:6px 0; }
  .co-line span:first-child { color:var(--muted,#6b7570); }
  .co-total { display:flex; justify-content:space-between; align-items:baseline; margin-top:10px; padding-top:14px; border-top:1px solid var(--line,#e6e2da); }
  .co-total span { font-size:13px; font-weight:600; }
  .co-total b { font-family:'Newsreader',serif; font-weight:500; font-size:32px; color:var(--accent-dk,#16743f); }

  /* Done */
  .co-done { max-width:520px; margin:0 auto; text-align:center; }
  .co-check { width:68px; height:68px; border-radius:50%; background:#eaf7ef; color:var(--accent-dk,#16743f); display:grid; place-items:center; margin:0 auto 18px; }
  .co-check svg { width:32px; height:32px; stroke:currentColor; fill:none; stroke-width:2.6; stroke-linecap:round; stroke-linejoin:round; }
  .co-done h1 { font-family:'Newsreader',Georgia,serif; font-weight:500; font-size:clamp(28px,4vw,36px); margin-bottom:8px; }
  .co-done > p { color:var(--muted,#6b7570); margin-bottom:22px; }
  .co-done .co-card { text-align:left; margin-bottom:22px; }
  .co-actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }

  @media (max-width:820px) { .co-grid { grid-template-columns:1fr; } .co-summary { order:-1; } }
</style>

<div class="co-wrap">

<?php
  // Reusable summary card
  $summary = function () use ($order, $domain, $fallback, $cost, $fee, $total, $slotsLeft) { ?>
    <div class="co-card co-summary">
      <h2>Order summary</h2>
      <div class="co-item">
        <span class="co-tile">
          <?php if ($domain !== ''): ?>
            <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128" alt="" width="30" height="30"
                 onerror="this.replaceWith(document.createTextNode('<?= $fallback ?>'))">
          <?php else: ?><?= $fallback ?><?php endif; ?>
        </span>
        <div>
          <h3><?= e($order['name']) ?></h3>
          <div class="meta"><?= e($order['category']) ?> · <?= (int) $order['members_count'] ?>/<?= (int) $order['max_members'] ?> slots filled<?= $slotsLeft > 0 ? ' · ' . $slotsLeft . ' open' : '' ?></div>
        </div>
      </div>
      <div class="co-line"><span>Your share</span><span><?= e(peso($cost)) ?></span></div>
      <div class="co-line"><span>Platform fee</span><span><?= e(peso($fee)) ?></span></div>
      <div class="co-total"><span>Total due</span><b><?= e(peso($total)) ?></b></div>
    </div>
<?php }; ?>

<?php if ($isPaid): ?>

  <div class="co-done">
    <div class="co-check"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
    <h1><?= $justPaid ? 'Payment complete' : 'Already paid' ?></h1>
    <p><?= $justPaid ? "You're in. Your slot for " . e($order['name']) . ' is secured.' : 'Your slot for ' . e($order['name']) . ' is already paid for.' ?></p>
    <?php $summary(); ?>
    <div class="co-actions">
      <a class="btn" href="/dashboard.php">Go to my subscriptions</a>
      <a class="btn-line" href="/browse.php" style="display:inline-block;padding:13px 26px;border:1.5px solid var(--ink,#1d2320);border-radius:999px;font-weight:500;font-size:15px">Browse more</a>
    </div>
  </div>

<?php else: ?>

  <div class="co-head">
    <h1><?= $isMember ? 'Checkout' : 'Join group' ?></h1>
    <p><?= $isMember ? 'Confirm your slot in' : 'Pay to take a slot in' ?> <?= e($order['name']) ?>. Payment is simulated, so nothing is actually charged.</p>
  </div>

  <div class="co-grid">
    <div class="co-card">
      <h2>Payment method</h2>

      <?php if ($errors): ?>
        <div class="co-alert" role="alert"><?= e(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <form method="POST" action="/checkout.php?group=<?= (int) $groupId ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="group_id" value="<?= (int) $groupId ?>">

        <div class="co-methods">
          <?php foreach ($methods as $key => [$label, $desc]): ?>
            <label class="co-method">
              <input type="radio" name="method" value="<?= e($key) ?>" <?= $method === $key ? 'checked' : '' ?>>
              <span class="dot"></span>
              <span><b><?= e($label) ?></b><span class="d"><?= e($desc) ?></span></span>
            </label>
          <?php endforeach; ?>
        </div>

        <button type="submit" class="btn co-pay" data-turbo-submits-with="Processing…">Pay <?= e(peso($total)) ?></button>
        <p class="co-note">This is a student project prototype. No real payment is made.</p>
      </form>
    </div>

    <?php $summary(); ?>
  </div>

<?php endif; ?>

</div>

<?php app_footer(); ?>