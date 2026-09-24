<?php
// public/index.php — Landing page

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';

header('Cache-Control: no-store, max-age=0');

$isLoggedIn = is_logged_in();

// Errors / old input from auth-handler.php (shown inside the hero form)
$authErrors = $_SESSION['auth_errors'] ?? [];
$authMode   = (!$isLoggedIn && $authErrors) ? ($_SESSION['auth_mode'] ?? 'login') : null;

// Sent here by require_login() (?redirect=/some/page): open the login form and remember where to go after
$redirectTo = (string) ($_GET['redirect'] ?? '/home.php');
if (!str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
    $redirectTo = '/home.php';
}
if (!$isLoggedIn && !$authMode && isset($_GET['redirect'])) {
    $authMode = 'login';
}
$oldName    = $_SESSION['old_name'] ?? '';
$oldEmail   = $_SESSION['old_email'] ?? '';
unset($_SESSION['auth_errors'], $_SESSION['auth_mode'], $_SESSION['old_name'], $_SESSION['old_email']);

// [name, domain, category, fallback emoji, max savings %, description]
$catalog = [
  ['Netflix','netflix.com','Entertainment','🎬',73,'Streaming service for movies, series and original shows you can watch on any device.'],
  ['Crunchyroll','crunchyroll.com','Entertainment','🍥',75,'Anime streaming with a huge library, including new episodes right after they air in Japan.'],
  ['Spotify Premium','spotify.com','Entertainment','🎵',72,'Ad-free music and podcasts with offline downloads and unlimited skips.'],
  ['Disney+','disneyplus.com','Entertainment','🏰',73,'Streaming home of Disney, Pixar, Marvel, Star Wars and National Geographic.'],
  ['Turnitin','turnitin.com','Education','📄',75,'Plagiarism checker that schools use to review how original a paper is.'],
  ['Grammarly','grammarly.com','Education','✍️',83,'Writing assistant that fixes grammar, spelling, clarity and tone as you type.'],
  ['Quizlet Plus','quizlet.com','Education','🧠',75,'Flashcards and study modes for exams, with no ads and extra practice tests.'],
  ['Coursera Plus','coursera.org','Education','🎓',83,'Unlimited access to thousands of online courses and certificates from top universities.'],
  ['Canva Pro','canva.com','Creative','🎨',80,'Design tool with premium templates, a background remover and brand kits.'],
  ['Adobe Creative Cloud','adobe.com','Creative','🖌️',83,'Photoshop, Illustrator, Premiere Pro and more of the industry-standard creative apps.'],
  ['CapCut Pro','capcut.com','Creative','✂️',80,'Video editor with premium effects, templates and exports without a watermark.'],
  ['Microsoft 365','microsoft.com','Productivity','💻',83,'Word, Excel, PowerPoint and Teams, plus 1 TB of OneDrive cloud storage.'],
  ['Notion Plus','notion.so','Productivity','📝',83,'Notes, docs and project planning together in one organized workspace.'],
  ['ChatGPT Plus','chatgpt.com','Productivity','🤖',75,'AI assistant for writing, coding and study help, with priority access to newer models.'],
  ['Perplexity Pro','perplexity.ai','Research','🔬',80,'AI research assistant that answers your questions with cited sources.'],
  ['Wolfram Alpha Pro','wolframalpha.com','Research','🧮',75,'Step-by-step solutions for math, science and data questions.'],
];
$rows = [
  ['dir' => 'right', 'items' => array_slice($catalog, 0, 8)],
  ['dir' => 'left',  'items' => array_slice($catalog, 8)],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ShareHub — Split subscriptions with other students</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:opsz,wght@6..72,500;6..72,600&display=swap" rel="stylesheet">
<style>
  :root { --bg:#f7f5f0; --green:#1fa35c; --green-dk:#16743f; --blue:#1d2320; --ink:#1d2320; --muted:#6b7570; --line:#e6e2da; --soft:#f0ece3; }
  * { box-sizing:border-box; margin:0; padding:0; }
  html { scroll-behavior:smooth; scroll-padding-top:80px; scrollbar-width:none; -ms-overflow-style:none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display:none; width:0; height:0; }
  body { font-family:'Inter',system-ui,sans-serif; color:var(--ink); background:var(--bg); line-height:1.55; overflow-x:clip; -webkit-text-size-adjust:100%; }
  a { color:inherit; text-decoration:none; }
  h1,h2,h3.serif { font-family:'Newsreader',Georgia,serif; font-weight:500; line-height:1.12; letter-spacing:-.01em; }
  .wrap { width:min(1120px,100% - 3rem); margin:0 auto; }

  /* Nav */
  .nav { position:sticky; top:0; z-index:50; background:rgba(247,245,240,.92); backdrop-filter:blur(10px); }
  .nav .wrap { display:flex; align-items:center; height:68px; gap:34px; }
  .logo { font-family:'Newsreader',serif; font-size:24px; font-weight:600; color:var(--ink); }
  .links { display:flex; gap:28px; flex:1; font-size:13px; font-weight:500; }
  .links a, .acct a { display:inline-flex; align-items:center; gap:8px; color:var(--ink); opacity:.75; font-size:13px; font-weight:500; transition:opacity .2s, background .2s, transform .2s; }
  .links a:hover, .acct a:hover { opacity:1; }
  .acct { align-items:center; }
  .pill-btn { background:#e9e5dc; padding:10px 20px; border-radius:999px; opacity:1 !important; }
  .pill-btn:hover { background:#ddd8cc; transform:translateY(-2px); }
  .links svg { width:17px; height:17px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
  .acct { display:flex; gap:26px; }
  .burger { display:none; width:40px; height:40px; border:0; background:none; cursor:pointer; padding:10px 8px; flex-direction:column; justify-content:space-between; }
  .burger span { display:block; height:2px; border-radius:2px; background:var(--ink); transition:transform .3s, opacity .3s; }
  .nav.menu-open .burger span:nth-child(1) { transform:translateY(8px) rotate(45deg); }
  .nav.menu-open .burger span:nth-child(2) { opacity:0; }
  .nav.menu-open .burger span:nth-child(3) { transform:translateY(-8px) rotate(-45deg); }

  /* Buttons */
  .btn { display:inline-flex; align-items:center; padding:13px 30px; border-radius:999px; background:var(--blue); color:#fff; font-weight:500; font-size:15px; box-shadow:0 0 0 rgba(0,0,0,0); transition:background .25s, transform .25s, box-shadow .25s, color .25s; }
  .btn::after { content:"→"; display:inline-block; width:0; margin-left:0; opacity:0; overflow:hidden; transform:translateX(-6px); transition:width .25s, margin-left .25s, opacity .25s, transform .25s; }
  .btn:hover { background:var(--green-dk); transform:translateY(-3px); box-shadow:0 10px 22px rgba(0,0,0,.2); }
  .btn:hover::after { width:1.1em; margin-left:10px; opacity:1; transform:none; }
  .btn:active { transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,0,0,.2); }
  .btn.white { background:#fff; color:var(--ink); }
  .hero-text .btn { background:#fff; color:var(--ink); }
  .hero-text .btn:hover { background:#e5f5ec; color:var(--green-dk); }
  .btn.white:hover { background:#e5f5ec; color:var(--green-dk); box-shadow:0 10px 22px rgba(0,0,0,.2); }
  .btn:focus-visible, .btn-line:focus-visible { outline:3px solid var(--green); outline-offset:3px; }

  /* Hero */
  .hero { position:relative; min-height:calc(100vh - 68px); max-height:760px; display:flex; align-items:flex-end; justify-content:center; text-align:center; color:#fff;
    background:linear-gradient(180deg,rgba(0,0,0,.05) 35%,rgba(0,0,0,.65) 100%),url('/images/hero.jpg') center 30%/cover no-repeat,#24503a; }
  .hero-in { padding:0 24px 88px; max-width:760px; }
  .hero h1 { font-size:clamp(36px,5.2vw,58px); margin-bottom:18px; text-shadow:0 2px 14px rgba(0,0,0,.35); }
  .hero p { font-size:clamp(16px,1.8vw,19px); margin-bottom:28px; text-shadow:0 1px 8px rgba(0,0,0,.4); }

  /* Hero: text swaps with the login / register form */
  .hero { overflow:hidden; }
  .hero-in { display:grid; grid-template-columns:minmax(0,1fr) 0px; column-gap:0; align-self:stretch; align-items:end; width:min(1120px,100% - 3rem); max-width:none; padding:0;
    transition:grid-template-columns .85s cubic-bezier(.22,1,.36,1), column-gap .85s cubic-bezier(.22,1,.36,1), opacity .2s linear; }
  .hero.auth-open .hero-in { grid-template-columns:minmax(0,1fr) 410px; column-gap:56px; }

  /* Hero text */
  .hero-text {
    align-self:center;
    margin-bottom:0;
    padding-inline:max(0px, calc((100% - 720px) / 2));
    transition:
      padding .85s cubic-bezier(.22,1,.36,1),
      opacity .3s ease,
      transform .85s cubic-bezier(.22,1,.36,1);
  }

  .hero h1 {
    transition:font-size .6s ease;
  }

  /* Auth opened */
  .hero.auth-open .hero-text {
    padding-left:0;
    padding-right:max(0px, calc(100% - 540px));
    align-self:center;
    margin-bottom:0;
    text-align:left;
    animation:textIn .9s ease both;
  }

  .hero.auth-open h1 {
    font-size:clamp(34px,4.4vw,50px);
  }

  /* Closing auth */
  .hero.auth-closing .hero-text {
    animation:textOut .9s ease both;
  }

  @keyframes textIn {
    0% {
      opacity:1;
      transform:none;
    }

    30% {
      opacity:0;
      transform:translateX(-20px);
    }

    31% {
      opacity:0;
    }

    100% {
      opacity:1;
      transform:none;
    }
  }

  @keyframes textOut {
    0% {
      opacity:1;
      transform:none;
    }

    30% {
      opacity:0;
      transform:translateX(-20px);
    }

    31% {
      opacity:0;
      transform:translateX(0);
    }

    100% {
      opacity:1;
      transform:none;
    }
  }

  /* form: slides in from the right corner */
  .hero-form { align-self:center; justify-self:start; width:410px; max-width:100%; opacity:0; visibility:hidden; transform:translateX(calc(100% + 140px)); pointer-events:none;
    transition:transform .85s cubic-bezier(.22,1,.36,1), opacity .5s ease, visibility 0s linear .85s; }
  .hero.auth-open .hero-form { opacity:1; visibility:visible; transform:none; pointer-events:auto; transition-delay:.1s, .1s, 0s; }

  .auth-card { background:rgba(255,255,255,.97); color:var(--ink); text-align:left; border-radius:20px; padding:26px 28px 24px; box-shadow:0 24px 60px rgba(0,0,0,.35); }
  .auth-back { background:none; border:0; font:inherit; font-size:14px; color:var(--muted); cursor:pointer; padding:0; margin-bottom:10px; transition:color .2s; }
  .auth-back:hover { color:var(--ink); }
  .auth-pane { animation:paneIn .45s cubic-bezier(.22,1,.36,1); }
  .auth-pane[hidden] { display:none; }
  @keyframes paneIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }
  .auth-card h2 { font-size:28px; margin-bottom:4px; }
  .auth-sub { color:var(--muted); font-size:15px; margin-bottom:16px; }
  .a-field { margin-bottom:11px; }
  .a-field label { display:block; font-size:12px; font-weight:700; color:var(--muted); margin:0 0 4px 14px; }
  .a-field input { width:100%; padding:12px 18px; border:1.5px solid var(--line); border-radius:999px; font:inherit; font-size:15px; color:var(--ink); background:#fff; transition:border-color .2s, box-shadow .2s; }
  .a-field input:hover { border-color:#c9cfd6; }
  .a-field input:focus { outline:none; border-color:var(--green); box-shadow:0 0 0 4px rgba(31,163,92,.16); }
  .a-hint { display:block; margin:4px 0 0 14px; font-size:12px; color:var(--muted); }
  .auth-card .btn { width:100%; justify-content:center; margin-top:6px; border:0; cursor:pointer; font-family:inherit; }
  .auth-switch { text-align:center; margin-top:14px; font-size:14px; color:var(--muted); }
  .auth-switch button { background:none; border:0; font:inherit; font-weight:700; color:var(--green-dk); cursor:pointer; padding:0; }
  .auth-switch button:hover { text-decoration:underline; }
  .hero { min-height:max(calc(100vh - 68px), 660px); min-height:max(calc(100svh - 68px), 660px); }
  .auth-alert { background:#fdecec; color:#a02b2b; border-radius:14px; padding:10px 14px; margin-bottom:12px; font-size:14px; }
  .auth-alert ul { list-style:none; }
  .auth-alert li + li { margin-top:3px; }
  /* opened by the server (after a failed login / register): show final state instantly */
  .hero.no-anim .hero-in, .hero.no-anim .hero-text, .hero.no-anim .hero-form, .hero.no-anim h1 { transition:none; }
  .hero.no-anim .hero-text { animation:none; text-align:left; align-self:center; margin-bottom:0; }
  @media (max-width:900px) {
    .hero-in, .hero.auth-open .hero-in { grid-template-columns:minmax(0,1fr); column-gap:0; }
    .hero-text, .hero-form { grid-area:1/1; }
    .hero-text, .hero.auth-open .hero-text { padding-inline:0; animation:none; transition:transform .7s cubic-bezier(.22,1,.36,1), opacity .5s ease; }
    .hero.auth-open .hero-text { opacity:0; transform:translateX(-60px); pointer-events:none; text-align:center; }
    .hero-form { justify-self:center; transform:translateX(70px); }
    .hero-form { margin-bottom:0; }
    .hero.no-anim .hero-text { text-align:center; }
    .hero.auth-open h1 { font-size:clamp(34px,5vw,54px); }
  }
  @media (prefers-reduced-motion:reduce) { .hero-in, .hero-text, .hero-form, .auth-pane { transition:none !important; animation:none !important; } }

  /* Sections */
  section { padding:clamp(56px,8vw,96px) 0; }
  section.soft { background:var(--soft); }
  .title { text-align:center; max-width:640px; margin:0 auto 44px; }
  .title h2 { font-size:clamp(28px,3.6vw,40px); margin-bottom:10px; }
  .title p { color:var(--muted); font-size:16px; }

  .steps { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:28px; text-align:left; }
  .step .n { font-family:'Newsreader',serif; font-size:40px; font-weight:500; line-height:1; color:var(--green); margin-bottom:8px; }
  .step h3 { font-size:16px; font-weight:600; margin-bottom:4px; }
  .step p { color:var(--muted); font-size:13.5px; }

  /* Marquee cards */
  .marquee { overflow:hidden; padding:22px 0; -webkit-mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent); mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent); }
  .marquee:hover { -webkit-mask-image:none; mask-image:none; }
  .track { display:flex; width:max-content; animation:slide-right 45s linear infinite; }
  .track.left { animation-name:slide-left; animation-duration:55s; }
  .marquee:hover .track { animation-play-state:paused; }
  .kpi { width:260px; flex:none; margin-right:16px; padding:20px; background:#fff; border:1px solid var(--line); border-radius:16px; position:relative; overflow:hidden; transition:transform .25s, border-color .25s, box-shadow .25s; }
  .kpi:hover { border-color:var(--green); transform:translateY(-5px); box-shadow:0 14px 30px rgba(0,0,0,.1); z-index:5; }
  .kpi-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
  .kpi-logo { width:52px; height:52px; border-radius:12px; border:1px solid var(--line); display:grid; place-items:center; font-size:24px; background:#fff; }
  .kpi-logo img { width:32px; height:32px; object-fit:contain; }
  .kpi-cat { font-size:11px; font-weight:500; color:var(--ink); background:var(--soft); padding:3px 10px; border-radius:999px; }
  .kpi-name { font-family:'Inter',sans-serif; font-size:16px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .kpi-save { margin:6px 0 12px; }
  .kpi-save small { display:block; font-size:13px; color:var(--muted); }
  .kpi-pct { font-family:'Newsreader',serif; font-size:50px; line-height:1.05; color:var(--green-dk); }
  .kpi-pct span { font-size:28px; }
  .kpi-foot { padding-top:12px; border-top:1px dashed var(--line); font-size:13px; color:var(--muted); }
  .kpi-desc { position:absolute; left:0; right:0; bottom:0; height:64%; padding:18px 20px; background:var(--green-dk); color:#fff; transform:translateY(101%); transition:transform .8s cubic-bezier(.22,1,.36,1); white-space:normal; z-index:2; }
  .kpi-desc strong { display:block; font-size:13px; margin-bottom:6px; color:#bff0d3; }
  .kpi-desc p { font-size:14px; line-height:1.5; }
  .kpi:hover .kpi-desc, .kpi.open .kpi-desc { transform:translateY(0); }
  .marquee:has(.kpi.open) .track { animation-play-state:paused; }
  @keyframes slide-right { from{transform:translateX(-50%)} to{transform:translateX(0)} }
  @keyframes slide-left  { from{transform:translateX(0)} to{transform:translateX(-50%)} }
  @media (prefers-reduced-motion:reduce) { .track{animation:none} .marquee{overflow-x:auto} }
  .center { text-align:center; margin-top:26px; }
  .btn-line { display:inline-block; padding:11px 26px; border:1.5px solid var(--ink); border-radius:999px; font-weight:500; font-size:14px; transition:background .25s, color .25s, transform .25s, box-shadow .25s; }
  .btn-line:hover { background:var(--ink); color:#fff; transform:translateY(-3px); box-shadow:0 10px 22px rgba(0,0,0,.15); }
  .btn-line:active { transform:translateY(-1px); }

  /* Join / create cards (text only) */
  .split { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr)); gap:24px; }
  .info-card { padding:clamp(30px,4vw,48px); border-radius:20px; display:flex; flex-direction:column; align-items:flex-start; }
  .info-card.join { background:#fff; border:1px solid var(--line); }
  .info-card.create { background:linear-gradient(135deg,#0b8a80,#43c3a6); color:#fff; }
  .info-card h3 { font-family:'Newsreader',serif; font-weight:500; line-height:1.1; font-size:clamp(26px,3vw,34px); margin-bottom:10px; }
  .info-card p { font-size:16px; margin-bottom:20px; color:var(--muted); }
  .info-card.create p { color:rgba(255,255,255,.88); }
  .info-card ul { list-style:none; margin-bottom:28px; }
  .info-card li { padding:6px 0 6px 28px; position:relative; font-size:15px; }
  .info-card li::before { content:"✓"; position:absolute; left:0; font-weight:700; color:var(--green); }
  .info-card.create li::before { color:#fff; }
  .info-card .btn { margin-top:auto; }

  /* Smooth scroll effects */
  .nav { transition:box-shadow .3s; }
  .nav.scrolled { box-shadow:0 1px 0 var(--line); }
  .js [data-reveal] { opacity:0; transform:translateY(22px); transition:opacity .8s cubic-bezier(.22,1,.36,1), transform .8s cubic-bezier(.22,1,.36,1); transition-delay:var(--d,0s); }
  .js [data-reveal].in { opacity:1; transform:none; }
  @media (prefers-reduced-motion:reduce) { .btn, .btn-line { transition:none; } .btn:hover, .btn-line:hover { transform:none; } .js [data-reveal] { opacity:1; transform:none; transition:none; } html { scroll-behavior:auto; } }

  /* CTA + footer */
  .cta { background:var(--green-dk); color:#fff; text-align:center; }
  .cta h2 { font-size:clamp(28px,4vw,42px); margin-bottom:12px; }
  .cta p { max-width:520px; margin:0 auto 26px; opacity:.92; font-size:16px; }
  footer { padding:36px 0 44px; text-align:center; color:var(--muted); font-size:13px; }

  @media (max-width:900px) {
    .nav .wrap { gap:12px; }
    .logo { margin-right:auto; }
    .burger { display:flex; }
    .links { display:none; position:absolute; top:68px; left:0; right:0; flex-direction:column; gap:0; background:var(--bg); border-bottom:1px solid var(--line); box-shadow:0 14px 24px rgba(0,0,0,.08); padding:8px 24px 16px; }
    .nav.menu-open .links { display:flex; }
    .links a { padding:14px 0; border-bottom:1px solid var(--line); font-size:15px; opacity:1; }
    .links a:last-child { border-bottom:0; }
    .hero { max-height:none; }
    .hero-in { padding:32px 0; }
    .hero-text { margin-bottom:24px; }
    .hero.no-anim .hero-text, .hero.auth-open .hero-text { margin-bottom:24px; }
  }
  @media (max-width:520px) {
    .acct { gap:10px; }
    .acct a { font-size:13px; }
    .pill-btn { padding:9px 16px; }
    .wrap { width:min(1120px,100% - 2rem); }
    .hero-in { width:calc(100% - 2rem); }
    .hero p { font-size:16px; }
    .btn { padding:13px 28px; font-size:15px; }
    .auth-card { padding:22px 20px 20px; border-radius:18px; }
    .a-field input { font-size:16px; }
    .title { margin-bottom:30px; }
    .title p { font-size:16px; }
    .kpi { width:236px; padding:18px; }
    .kpi-pct { font-size:44px; }
    .info-card .btn, .cta .btn { width:100%; justify-content:center; }
    .cta p { font-size:16px; }
  }
</style>
</head>
<body>

<header class="nav">
  <div class="wrap">
    <a href="/" class="logo">ShareHub</a>
    <nav class="links">
      <a href="#browse">Browse subscriptions</a>
      <a href="#sell">Create a group</a>
      <a href="#how">How it works</a>
    </nav>
    <div class="acct">
      <?php if ($isLoggedIn): ?>
        <a href="/home.php" class="pill-btn">Home</a>
      <?php else: ?>
        <a href="/login.php">Sign in</a>
        <a href="/register.php" class="pill-btn">Sign up</a>
      <?php endif; ?>
    </div>
    <button type="button" class="burger" id="burger" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
  </div>
</header>

<section class="hero<?= $authMode ? ' auth-open no-anim' : '' ?>" style="padding:0">
  <div class="hero-in">
    <div class="hero-text" id="heroText">
      <h1>Split the plan. Keep the savings.</h1>
      <p>ShareHub connects students to share subscriptions like Netflix, Canva and Microsoft 365, so everyone pays only their share.</p>
      <a href="<?= $isLoggedIn ? '/browse.php' : '/register.php' ?>" class="btn">Get started</a>
    </div>

    <?php if (!$isLoggedIn): ?>
    <div class="hero-form" id="authBox"<?= $authMode ? '' : ' inert' ?>>
      <div class="auth-card">
        <button type="button" class="auth-back" data-close>← Back</button>

        <div class="auth-pane" data-pane="login"<?= $authMode === 'login' ? '' : ' hidden' ?>>
          <h2>Welcome back</h2>
          <div class="auth-sub">Log in to see your subscriptions and renewal dates.</div>
          <?php if ($authMode === 'login' && $authErrors): ?>
            <div class="auth-alert" role="alert"><ul><?php foreach ($authErrors as $err): ?><li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
          <?php endif; ?>
          <form method="POST" action="/auth-handler.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8') ?>">
            <div class="a-field"><label for="l-email">Email</label><input id="l-email" name="email" type="email" value="<?= htmlspecialchars($authMode === 'login' ? $oldEmail : '', ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email"></div>
            <div class="a-field"><label for="l-pass">Password</label><input id="l-pass" name="password" type="password" required autocomplete="current-password"></div>
            <button type="submit" class="btn">Login</button>
          </form>
          <div class="auth-switch">Don't have an account? <button type="button" data-mode="register">Register</button></div>
        </div>

        <div class="auth-pane" data-pane="register"<?= $authMode === 'register' ? '' : ' hidden' ?>>
          <h2>Create an account</h2>
          <div class="auth-sub">Sign up and split your first plan.</div>
          <?php if ($authMode === 'register' && $authErrors): ?>
            <div class="auth-alert" role="alert"><ul><?php foreach ($authErrors as $err): ?><li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
          <?php endif; ?>
          <form method="POST" action="/auth-handler.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="register">
            <div class="a-field"><label for="r-name">Name</label><input id="r-name" name="name" type="text" value="<?= htmlspecialchars($authMode === 'register' ? $oldName : '', ENT_QUOTES, 'UTF-8') ?>" required minlength="2" maxlength="100" autocomplete="name"></div>
            <div class="a-field"><label for="r-email">Email</label><input id="r-email" name="email" type="email" value="<?= htmlspecialchars($authMode === 'register' ? $oldEmail : '', ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email"></div>
            <div class="a-field"><label for="r-pass">Password</label><input id="r-pass" name="password" type="password" required minlength="8" autocomplete="new-password"><span class="a-hint">At least 8 characters.</span></div>
            <div class="a-field"><label for="r-pass2">Confirm Password</label><input id="r-pass2" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
            <button type="submit" class="btn">Register</button>
          </form>
          <div class="auth-switch">Already have an account? <button type="button" data-mode="login">Login</button></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<section id="how">
  <div class="wrap">
    <div class="title" data-reveal><h2>How it works</h2><p>From browsing to your first shared plan in a few minutes.</p></div>
    <div class="steps">
      <div class="step" data-reveal style="--d:0s"><div class="n">1</div><h3>Browse</h3><p>Pick a category: education, entertainment, editing, productivity or research.</p></div>
      <div class="step" data-reveal style="--d:.08s"><div class="n">2</div><h3>Join or create</h3><p>Take a slot in an open group, or start your own and invite others.</p></div>
      <div class="step" data-reveal style="--d:.16s"><div class="n">3</div><h3>Checkout</h3><p>Pay your share plus a small platform fee. Payment is simulated in this prototype.</p></div>
      <div class="step" data-reveal style="--d:.24s"><div class="n">4</div><h3>Manage</h3><p>See your active subscriptions and renewal dates in your dashboard.</p></div>
    </div>
  </div>
</section>

<section id="browse" class="soft">
  <div class="wrap">
    <div class="title" data-reveal><h2>Listed subscriptions</h2><p>Streaming, school tools, editing and research, all shared with other students.</p></div>
  </div>
  <?php foreach ($rows as $row): ?>
    <div class="marquee" data-reveal>
      <div class="track <?= $row['dir'] ?>">
        <?php for ($copy = 0; $copy < 2; $copy++): ?>
          <?php foreach ($row['items'] as [$name, $domain, $cat, $fallback, $pct, $desc]): ?>
            <div class="kpi" <?= $copy ? 'aria-hidden="true"' : '' ?>>
              <div class="kpi-top">
                <span class="kpi-logo">
                  <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128" alt="" loading="lazy" width="32" height="32"
                       onerror="this.replaceWith(document.createTextNode('<?= $fallback ?>'))">
                </span>
                <span class="kpi-cat"><?= htmlspecialchars($cat) ?></span>
              </div>
              <h3 class="kpi-name"><?= htmlspecialchars($name) ?></h3>
              <div class="kpi-save"><small>Save as much as</small><div class="kpi-pct"><?= (int)$pct ?><span>%</span></div></div>
              <div class="kpi-foot">when you split with a group</div>
              <div class="kpi-desc"><strong>About <?= htmlspecialchars($name) ?></strong><p><?= htmlspecialchars($desc) ?></p></div>
            </div>
          <?php endforeach; ?>
        <?php endfor; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="center" data-reveal><a href="<?= $isLoggedIn ? '/browse.php' : '/login.php' ?>" class="btn-line">See all subscriptions</a></div>
</section>

<section id="sell">
  <div class="wrap">
    <div class="split">
      <div class="info-card join" data-reveal>
        <h3>Need a plan?</h3>
        <p>Join a group and get access for a fraction of the full price.</p>
        <ul>
          <li>See your share before you join</li>
          <li>Track renewal dates in your dashboard</li>
          <li>Leave a group anytime</li>
        </ul>
        <a href="<?= $isLoggedIn ? '/browse.php' : '/register.php' ?>" class="btn">Find a group</a>
      </div>
      <div class="info-card create" data-reveal style="--d:.12s">
        <h3>Already have one?</h3>
        <p>Create a group, set the slots, and let others share the cost with you.</p>
        <ul>
          <li>Choose how many slots to open</li>
          <li>Invite classmates or open it to everyone</li>
          <li>Get your share covered every month</li>
        </ul>
        <a href="<?= $isLoggedIn ? '/create-group.php' : '/register.php' ?>" class="btn white">Create a group</a>
      </div>
    </div>
  </div>
</section>

<section class="cta">
  <div class="wrap" data-reveal>
    <h2>Pay less for the tools you already need</h2>
    <p>You pay your share of the plan plus a small platform fee, shown clearly at checkout.</p>
    <a href="<?= $isLoggedIn ? '/browse.php' : '/register.php' ?>" class="btn white">Get started</a>
  </div>
</section>

<footer>© <?= date('Y') ?> ShareHub. Student project prototype, so payments are simulated.</footer>
<script>
  document.documentElement.classList.add('js');
  const nav = document.querySelector('.nav');
  const heroIn = document.querySelector('.hero-in');
  const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
  let ticking = false;

  function onScroll() {
    const y = window.scrollY;
    nav.classList.toggle('scrolled', y > 10);
    if (!still && y < window.innerHeight) {
      heroIn.style.opacity = Math.max(0, 1 - y / (window.innerHeight * 0.8));
    }
    ticking = false;
  }
  addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(onScroll); ticking = true; } }, { passive: true });
  onScroll();

  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el));
  // Mobile menu
  const burger = document.getElementById('burger');
  function setMenu(o) { nav.classList.toggle('menu-open', o); burger.setAttribute('aria-expanded', o); }
  burger.addEventListener('click', () => setMenu(!nav.classList.contains('menu-open')));
  document.querySelectorAll('.links a').forEach(a => a.addEventListener('click', () => setMenu(false)));
  addEventListener('keydown', e => { if (e.key === 'Escape') setMenu(false); });

  // Tap a subscription card to read its description (touch screens have no hover)
  document.querySelectorAll('.kpi').forEach(k => k.addEventListener('click', () => {
    const was = k.classList.contains('open');
    document.querySelectorAll('.kpi.open').forEach(o => o.classList.remove('open'));
    if (!was) k.classList.add('open');
  }));

  // Hero login / register swipe
  const authBox = document.getElementById('authBox');
  if (authBox) {
    const heroEl = document.querySelector('.hero');
    const heroText = document.getElementById('heroText');
    const panes = authBox.querySelectorAll('[data-pane]');
    let open = heroEl.classList.contains('auth-open');

    function showPane(mode) {
      panes.forEach(p => { p.hidden = p.dataset.pane !== mode; });
    }
    function openAuth(mode) {
      showPane(mode);
      window.scrollTo({ top: 0, behavior: still ? 'auto' : 'smooth' });
      if (!open) {
        open = true;
        heroEl.classList.remove('auth-closing');
        heroEl.classList.add('auth-open');
        authBox.inert = false;
      }
      setTimeout(() => { const i = authBox.querySelector('[data-pane]:not([hidden]) input'); if (i) i.focus({ preventScroll: true }); }, still ? 0 : 500);
    }
    function closeAuth() {
      open = false;
      heroEl.classList.remove('no-anim');
      heroEl.classList.add('auth-closing');
      heroEl.classList.remove('auth-open');
      setTimeout(() => { if (!open) heroEl.classList.remove('auth-closing'); }, 950);
      authBox.inert = true;
    }

    addEventListener('pageshow', (e) => {
      if (!e.persisted) return;
      open = false;
      heroEl.classList.remove('auth-open', 'auth-closing', 'no-anim');
      heroText.getAnimations().forEach(a => a.cancel());
      authBox.inert = true;
      authBox.querySelectorAll('input[type=password]').forEach(i => { i.value = ''; });
    });

    document.addEventListener('click', (e) => {
      const link = e.target.closest('a[href="/login.php"], a[href="/register.php"]');
      if (link) { e.preventDefault(); openAuth(link.getAttribute('href') === '/login.php' ? 'login' : 'register'); return; }
      const sw = e.target.closest('[data-mode]');
      if (sw) { showPane(sw.dataset.mode); const i = authBox.querySelector('[data-pane]:not([hidden]) input'); if (i) i.focus({ preventScroll: true }); return; }
      if (e.target.closest('[data-close]')) closeAuth();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && open) closeAuth(); });
  }
</script>
</body>
</html>