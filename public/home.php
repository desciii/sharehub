<?php
// public/home.php — Logged-in home page

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/nav.php';

require_login();

$user = current_user();
$firstName = explode(' ', trim($user['name']))[0] ?: 'there';
$catalog = require __DIR__ . '/../includes/catalog.php';
$categories = ['Entertainment', 'Education', 'Creative', 'Productivity', 'Research'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Home — ShareHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:opsz,wght@6..72,500;6..72,600&display=swap" rel="stylesheet">
<?php app_head_assets(); ?>
</head>
<body>

<?php app_nav('home'); ?>

<?php /* Page styles live in <body> on purpose: Turbo drops them when you leave the page, so they can't leak into other pages. */ ?>
<style>
  :root { --bg:#f7f5f0; --ink:#1d2320; --muted:#6b7570; --line:#e6e2da; --accent:#1fa35c; --accent-dk:#16743f; }
  * { box-sizing:border-box; margin:0; padding:0; }
  html { scroll-behavior:smooth; scroll-padding-top:80px; }
  body { font-family:'Inter',system-ui,sans-serif; background:var(--bg); color:var(--ink); line-height:1.55; }
  a { color:inherit; text-decoration:none; }
  h1,h2,h3.serif,.serif { font-family:'Newsreader',Georgia,serif; font-weight:500; line-height:1.12; letter-spacing:-.01em; }
  .wrap { width:min(1160px,100% - 3rem); margin:0 auto; }

  /* Hero */
  .hero { text-align:center; padding:56px 0 40px; }
  .greet { display:inline-block; font-size:13px; color:var(--muted); background:#fff; border:1px solid var(--line); border-radius:999px; padding:5px 14px; margin-bottom:18px; }
  .hero h1 { font-size:clamp(36px,5.2vw,58px); max-width:760px; margin:0 auto 16px; }
  .hero p { color:var(--muted); max-width:470px; margin:0 auto 34px; }

  .search { display:flex; align-items:center; max-width:780px; margin:0 auto; background:#fff; border-radius:999px; padding:6px 6px 6px 10px; box-shadow:0 10px 30px rgba(40,50,40,.08); text-align:left; }
  .seg { flex:1; padding:6px 20px; border-left:1px solid var(--line); min-width:0; }
  .seg:first-child { border-left:0; flex:1.4; }
  .seg label { display:block; font-size:11px; color:var(--muted); }
  .seg input, .seg select { width:100%; border:0; background:transparent; font:inherit; font-size:14px; font-weight:500; color:var(--ink); outline:none; padding:2px 0; }
  .seg:focus-within label { color:var(--accent-dk); }
  .go { flex:none; width:50px; height:50px; border-radius:50%; border:0; background:var(--accent); color:#fff; cursor:pointer; display:grid; place-items:center; transition:transform .2s, background .2s; }
  .go:hover { background:var(--accent-dk); transform:scale(1.06); }
  .go svg { width:20px; height:20px; stroke:#fff; fill:none; stroke-width:2.2; stroke-linecap:round; }

  /* Big photo cards */
  section { padding:34px 0; }
  .duo { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
  .photo { position:relative; height:320px; border-radius:20px; overflow:hidden; display:flex; align-items:flex-end; padding:28px; color:#fff; background-size:cover; background-position:center; transition:transform .4s cubic-bezier(.22,1,.36,1); }
  .photo:hover { transform:translateY(-4px); }
  .photo.a { background-image:linear-gradient(180deg,rgba(0,0,0,0) 35%,rgba(0,0,0,.55)),url('/images/home-1.jpg'); background-color:#2e7d62; }
  .photo.b { background-image:linear-gradient(180deg,rgba(0,0,0,0) 35%,rgba(0,0,0,.55)),url('/images/home-2.jpg'); background-color:#8a5a3c; }
  .photo h2 { font-size:clamp(26px,3vw,34px); margin-bottom:14px; text-shadow:0 2px 12px rgba(0,0,0,.3); }
  .white-pill { display:inline-block; background:#fff; color:var(--ink); padding:9px 20px; border-radius:999px; font-size:13px; font-weight:500; transition:transform .2s, box-shadow .2s; }
  .white-pill:hover { transform:translateY(-2px); box-shadow:0 8px 18px rgba(0,0,0,.2); }

  /* Section headers */
  .head { display:flex; align-items:end; justify-content:space-between; gap:16px; margin-bottom:18px; }
  .head h2 { font-size:clamp(28px,3.4vw,38px); }
  .link-accent { color:var(--accent-dk); font-size:13px; font-weight:500; border-bottom:1.5px solid var(--accent); padding-bottom:1px; }
  .center-head { text-align:center; margin-bottom:22px; }
  .center-head h2 { font-size:clamp(28px,3.6vw,40px); margin-bottom:8px; }
  .center-head p { color:var(--muted); }

  /* Quick action cards */
  .trio { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
  .deal { position:relative; height:210px; border-radius:16px; padding:22px; color:#fff; display:flex; flex-direction:column; justify-content:space-between; overflow:hidden; transition:transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s; }
  .deal:hover { transform:translateY(-5px); box-shadow:0 18px 34px rgba(0,0,0,.18); }
  .deal::after { content:""; position:absolute; right:-60px; top:-60px; width:200px; height:200px; border-radius:50%; background:rgba(255,255,255,.12); }
  .deal.g1 { background:linear-gradient(135deg,#0b8a80,#43c3a6); }
  .deal.g2 { background:linear-gradient(135deg,#6f3b30,#b9765d); }
  .deal.g3 { background:linear-gradient(135deg,#1f5fd6,#62a4ff); }
  .deal h3 { font-family:'Newsreader',serif; font-weight:500; font-size:26px; line-height:1.05; text-transform:uppercase; position:relative; z-index:1; }
  .deal p { font-size:13px; opacity:.92; margin-bottom:10px; position:relative; z-index:1; max-width:260px; }
  .tag { display:inline-block; background:#fff; color:var(--ink); font-size:12px; font-weight:600; padding:5px 12px; border-radius:6px; position:relative; z-index:1; }

  /* Tabs */
  .tabs { display:flex; justify-content:center; flex-wrap:wrap; gap:10px; margin-bottom:26px; }
  .tab { border:1px solid var(--line); background:#fff; color:var(--ink); font:inherit; font-size:13px; font-weight:500; padding:8px 18px; border-radius:999px; cursor:pointer; transition:background .2s, color .2s, border-color .2s, transform .2s; }
  .tab:hover { border-color:var(--ink); transform:translateY(-2px); }
  .tab.on { background:var(--accent); border-color:var(--accent); color:#fff; }

  /* Subscription cards */
  .grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; }
  .sub { background:#fff; border-radius:16px; overflow:hidden; border:1px solid var(--line); display:flex; flex-direction:column; transition:transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s; }
  .sub:hover { transform:translateY(-5px); box-shadow:0 16px 30px rgba(40,50,40,.12); }
  .sub[hidden] { display:none; }
  .media { position:relative; height:132px; display:grid; place-items:center; background:var(--tint,#eee); }
  .media .chip { position:absolute; top:12px; left:12px; background:#fff; font-size:11px; font-weight:500; padding:3px 10px; border-radius:999px; }
  .media .logo-tile { width:64px; height:64px; border-radius:16px; background:#fff; display:grid; place-items:center; font-size:30px; box-shadow:0 6px 16px rgba(0,0,0,.1); transition:transform .3s; }
  .sub:hover .logo-tile { transform:scale(1.08) rotate(-3deg); }
  .media img { width:38px; height:38px; object-fit:contain; }
  .c-Entertainment { --tint:#ffe6da; } .c-Education { --tint:#e2ecff; } .c-Creative { --tint:#f1e4ff; } .c-Productivity { --tint:#dff3e6; } .c-Research { --tint:#fff0cf; }
  .body { padding:16px; display:flex; flex-direction:column; flex:1; }
  .body h3 { font-size:15px; font-weight:600; font-family:'Inter',sans-serif; margin-bottom:4px; }
  .body p { font-size:12.5px; color:var(--muted); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-bottom:14px; }
  .foot { margin-top:auto; display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--muted); }
  .foot b { color:var(--accent-dk); font-size:15px; }
  .foot a { background:var(--ink); color:#fff; padding:6px 14px; border-radius:999px; font-weight:500; transition:background .2s; }
  .foot a:hover { background:var(--accent-dk); }

  /* How it works */
  .steps { display:grid; grid-template-columns:repeat(4,1fr); gap:24px; }
  .step .n { font-family:'Newsreader',serif; font-size:40px; color:var(--accent); line-height:1; margin-bottom:8px; }
  .step h3 { font-size:16px; font-weight:600; font-family:'Inter',sans-serif; margin-bottom:4px; }
  .step p { font-size:13.5px; color:var(--muted); }
  footer { padding:36px 0 44px; text-align:center; color:var(--muted); font-size:13px; }

  .js [data-reveal] { opacity:0; transform:translateY(20px); transition:opacity .8s cubic-bezier(.22,1,.36,1), transform .8s cubic-bezier(.22,1,.36,1); }
  .js [data-reveal].in { opacity:1; transform:none; }

  @media (max-width:1000px) { .grid { grid-template-columns:repeat(2,1fr); } .steps { grid-template-columns:repeat(2,1fr); } }
  @media (max-width:760px) {
    .duo, .trio { grid-template-columns:1fr; }
    .search { flex-direction:column; align-items:stretch; border-radius:22px; padding:10px; }
    .seg, .seg:first-child { border-left:0; border-bottom:1px solid var(--line); padding:8px 14px; }
    .go { width:100%; border-radius:999px; height:46px; margin-top:8px; }
  }
  @media (max-width:520px) { .grid { grid-template-columns:1fr; } }
  @media (prefers-reduced-motion:reduce) { * { transition:none !important; } .js [data-reveal] { opacity:1; transform:none; } html { scroll-behavior:auto; } }
</style>

<main class="wrap">

  <section class="hero">
    <span class="greet">Welcome back, <?= htmlspecialchars($firstName) ?></span>
    <h1>Find your next shared plan</h1>
    <p>Browse subscriptions other students are splitting, or open a group of your own.</p>

    <form class="search" action="/browse.php" method="GET">
      <div class="seg"><label for="q">Subscription</label><input id="q" name="q" type="text" placeholder="Netflix, Canva, Turnitin..."></div>
      <div class="seg"><label for="category">Category</label>
        <select id="category" name="category">
          <option value="">All categories</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="seg"><label for="slots">Open slots</label>
        <select id="slots" name="slots">
          <option value="">Any</option><option value="1">1+ slot</option><option value="2">2+ slots</option><option value="3">3+ slots</option>
        </select>
      </div>
      <button class="go" type="submit" aria-label="Search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg></button>
    </form>
  </section>

  <section>
    <div class="duo">
      <div class="photo a" data-reveal>
        <div><h2>Split more.<br>Pay less.</h2><a href="#how" class="white-pill">See how it works</a></div>
      </div>
      <div class="photo b" data-reveal style="transition-delay:.1s">
        <div><h2>Find your group<br>and start sharing.</h2><a href="/browse.php" class="white-pill">Browse subscriptions</a></div>
      </div>
    </div>
  </section>

  <section>
    <div class="head" data-reveal><h2>Jump in</h2><a href="/browse.php" class="link-accent">View all</a></div>
    <div class="trio">
      <a href="/browse.php" class="deal g1" data-reveal><h3>Join a<br>group</h3><div><p>Take an open slot and pay only your share.</p><span class="tag">Browse groups</span></div></a>
      <a href="/create-group.php" class="deal g2" data-reveal style="transition-delay:.08s"><h3>Create a<br>group</h3><div><p>Already have a plan? Split it with others.</p><span class="tag">Start a group</span></div></a>
      <a href="/dashboard.php" class="deal g3" data-reveal style="transition-delay:.16s"><h3>Your<br>subscriptions</h3><div><p>Check active plans and renewal dates.</p><span class="tag">Open dashboard</span></div></a>
    </div>
  </section>

  <section id="browse">
    <div class="center-head" data-reveal>
      <h2>Find the perfect plan<br>to share</h2>
      <p>Streaming, school tools, editing and research, all split with other students.</p>
    </div>
    <div class="tabs" id="tabs" data-reveal>
      <button class="tab on" data-cat="all">All</button>
      <?php foreach ($categories as $c): ?><button class="tab" data-cat="<?= $c ?>"><?= $c ?></button><?php endforeach; ?>
    </div>
    <div class="grid" id="grid">
      <?php foreach ($catalog as [$name, $domain, $cat, $fallback, $pct, $desc]): ?>
        <article class="sub c-<?= htmlspecialchars($cat) ?>" data-cat="<?= htmlspecialchars($cat) ?>">
          <div class="media">
            <span class="chip"><?= htmlspecialchars($cat) ?></span>
            <span class="logo-tile"><img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128" alt="" loading="lazy" width="38" height="38" onerror="this.replaceWith(document.createTextNode('<?= $fallback ?>'))"></span>
          </div>
          <div class="body">
            <h3><?= htmlspecialchars($name) ?></h3>
            <p><?= htmlspecialchars($desc) ?></p>
            <div class="foot"><span>Save up to <b><?= (int)$pct ?>%</b></span><a href="/browse.php?q=<?= urlencode($name) ?>">View</a></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:26px"><a href="/browse.php" class="link-accent">See all subscriptions</a></p>
  </section>

  <section id="how">
    <div class="center-head" data-reveal><h2>How it works</h2><p>From browsing to your first shared plan in a few minutes.</p></div>
    <div class="steps">
      <div class="step" data-reveal><div class="n">1</div><h3>Browse</h3><p>Pick a category: education, entertainment, editing, productivity or research.</p></div>
      <div class="step" data-reveal style="transition-delay:.08s"><div class="n">2</div><h3>Join or create</h3><p>Take a slot in an open group, or start your own and invite others.</p></div>
      <div class="step" data-reveal style="transition-delay:.16s"><div class="n">3</div><h3>Checkout</h3><p>Pay your share plus a small platform fee. Payment is simulated in this prototype.</p></div>
      <div class="step" data-reveal style="transition-delay:.24s"><div class="n">4</div><h3>Manage</h3><p>See your active subscriptions and renewal dates in your dashboard.</p></div>
    </div>
  </section>

</main>

<footer>© <?= date('Y') ?> ShareHub. Student project prototype, so payments are simulated.</footer>

<script>
(function () {
  document.documentElement.classList.add('js');

  // Category tabs: show up to 8 cards for the chosen category
  const tabs = document.querySelectorAll('.tab');
  const cards = document.querySelectorAll('#grid .sub');
  function filter(cat) {
    let shown = 0;
    cards.forEach(c => {
      const ok = (cat === 'all' || c.dataset.cat === cat) && shown < 8;
      c.hidden = !ok;
      if (ok) shown++;
    });
  }
  tabs.forEach(t => t.addEventListener('click', () => {
    tabs.forEach(x => x.classList.toggle('on', x === t));
    filter(t.dataset.cat);
  }));
  filter('all');

  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .15, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el));
})();
</script>
</body>
</html>