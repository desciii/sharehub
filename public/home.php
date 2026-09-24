<?php
// public/home.php — Logged-in home page

require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/nav.php';

require_login();

/* ---------- Helpers ---------- */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

/* ---------- State ---------- */

$user      = current_user();
$firstName = explode(' ', trim($user['name'] ?? ''))[0] ?: 'there';
$catalog   = require __DIR__ . '/../includes/catalog.php';

$categories = [
    'Entertainment',
    'Education',
    'Creative',
    'Productivity',
    'Research',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home — ShareHub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:opsz,wght@6..72,500;6..72,600&display=swap"
        rel="stylesheet"
    >

    <?php app_head_assets(); ?>

    <style>
        /* ===== Base (home page only) ===== */
        .home-page {
            --home-bg: #f7f5f0;
            --home-ink: #1d2320;
            --home-muted: #6b7570;
            --home-line: #e6e2da;
            --home-accent: #1fa35c;
            --home-accent-dk: #16743f;

            margin: 0;
            padding: 0;
            background: var(--home-bg);
            color: var(--home-ink);
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.55;
        }
        .home-page *,
        .home-page *::before,
        .home-page *::after { box-sizing: border-box; }
        .home-page a { color: inherit; text-decoration: none; }
        .home-page button,
        .home-page input,
        .home-page select { font: inherit; }

        /* ===== Page enter (plays on every visit, works with Turbo) ===== */
        .home-page .home-wrap { animation: home-page-in .45s cubic-bezier(.22, 1, .36, 1) both; }
        @keyframes home-page-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: none; }
        }

        .home-page .home-wrap {
            width: min(1160px, calc(100% - 3rem));
            margin: 0 auto;
        }
        .home-page .home-section { padding: 34px 0; }

        /* ===== Hero ===== */
        .home-page .home-hero {
            text-align: center;
            padding: 56px 0 40px;
        }
        .home-page .home-greet {
            display: inline-block;
            margin-bottom: 18px;
            padding: 5px 14px;
            border: 1px solid var(--home-line);
            border-radius: 999px;
            background: #fff;
            color: var(--home-muted);
            font-size: 13px;
        }
        .home-page .home-hero h1 {
            max-width: 760px;
            margin: 0 auto 16px;
            font-family: 'Newsreader', Georgia, serif;
            font-size: clamp(36px, 5.2vw, 58px);
            font-weight: 500;
            line-height: 1.12;
            letter-spacing: -.01em;
        }
        .home-page .home-hero > p {
            max-width: 470px;
            margin: 0 auto 34px;
            color: var(--home-muted);
        }

        /* ===== Search ===== */
        .home-page .home-search {
            display: flex;
            align-items: center;
            max-width: 780px;
            margin: 0 auto;
            padding: 6px 6px 6px 10px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(40, 50, 40, .08);
            text-align: left;
        }
        .home-page .home-search-seg {
            flex: 1;
            min-width: 0;
            padding: 6px 20px;
            border-left: 1px solid var(--home-line);
        }
        .home-page .home-search-seg:first-child { flex: 1.4; border-left: 0; }
        .home-page .home-search-seg label {
            display: block;
            font-size: 11px;
            color: var(--home-muted);
        }
        .home-page .home-search-seg input,
        .home-page .home-search-seg select {
            width: 100%;
            padding: 2px 0;
            border: 0;
            outline: none;
            background: transparent;
            color: var(--home-ink);
            font-size: 14px;
            font-weight: 500;
        }
        .home-page .home-search-seg:focus-within label { color: var(--home-accent-dk); }
        .home-page .home-search-go {
            flex: none;
            display: grid;
            place-items: center;
            width: 50px;
            height: 50px;
            border: 0;
            border-radius: 50%;
            background: var(--home-accent);
            color: #fff;
            cursor: pointer;
            transition: transform .2s ease, background .2s ease;
        }
        .home-page .home-search-go:hover {
            background: var(--home-accent-dk);
            transform: scale(1.06);
        }
        .home-page .home-search-go svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: #fff;
            stroke-width: 2.2;
            stroke-linecap: round;
        }

        /* ===== Photo cards ===== */
        .home-page .home-duo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .home-page .home-photo {
            position: relative;
            display: flex;
            align-items: flex-end;
            height: 320px;
            padding: 28px;
            overflow: hidden;
            border-radius: 20px;
            color: #fff;
            background-size: cover;
            background-position: center;
            transition: transform .4s cubic-bezier(.22, 1, .36, 1);
        }
        .home-page .home-photo:hover { transform: translateY(-4px); }
        .home-page .home-photo-a {
            background-image: linear-gradient(180deg, rgba(0, 0, 0, 0) 35%, rgba(0, 0, 0, .55)), url('/images/home-1.jpg');
            background-color: #2e7d62;
        }
        .home-page .home-photo-b {
            background-image: linear-gradient(180deg, rgba(0, 0, 0, 0) 35%, rgba(0, 0, 0, .55)), url('/images/home-2.jpg');
            background-color: #8a5a3c;
        }
        .home-page .home-photo h2 {
            margin: 0 0 14px;
            font-family: 'Newsreader', Georgia, serif;
            font-size: clamp(26px, 3vw, 34px);
            font-weight: 500;
            line-height: 1.12;
            letter-spacing: -.01em;
            text-shadow: 0 2px 12px rgba(0, 0, 0, .3);
        }
        .home-page .home-white-pill {
            display: inline-block;
            padding: 9px 20px;
            border-radius: 999px;
            background: #fff;
            color: var(--home-ink);
            font-size: 13px;
            font-weight: 500;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .home-page .home-white-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .2);
        }

        /* ===== Section headers ===== */
        .home-page .home-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }
        .home-page .home-head h2 {
            margin: 0;
            font-family: 'Newsreader', Georgia, serif;
            font-size: clamp(28px, 3.4vw, 38px);
            font-weight: 500;
            line-height: 1.12;
        }
        .home-page .home-link-accent {
            padding-bottom: 1px;
            border-bottom: 1.5px solid var(--home-accent);
            color: var(--home-accent-dk);
            font-size: 13px;
            font-weight: 500;
        }
        .home-page .home-center-head {
            margin-bottom: 22px;
            text-align: center;
        }
        .home-page .home-center-head h2 {
            margin: 0 0 8px;
            font-family: 'Newsreader', Georgia, serif;
            font-size: clamp(28px, 3.6vw, 40px);
            font-weight: 500;
            line-height: 1.12;
        }
        .home-page .home-center-head p { margin: 0; color: var(--home-muted); }
        .home-page .home-more { margin-top: 26px; text-align: center; }

        /* ===== Quick actions ===== */
        .home-page .home-trio {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .home-page .home-deal {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 210px;
            padding: 22px;
            overflow: hidden;
            border-radius: 16px;
            color: #fff;
            transition: transform .3s cubic-bezier(.22, 1, .36, 1), box-shadow .3s ease;
        }
        .home-page .home-deal:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 34px rgba(0, 0, 0, .18);
        }
        .home-page .home-deal::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .12);
        }
        .home-page .home-deal-g1 { background: linear-gradient(135deg, #0b8a80, #43c3a6); }
        .home-page .home-deal-g2 { background: linear-gradient(135deg, #6f3b30, #b9765d); }
        .home-page .home-deal-g3 { background: linear-gradient(135deg, #1f5fd6, #62a4ff); }
        .home-page .home-deal h3 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-family: 'Newsreader', Georgia, serif;
            font-size: 26px;
            font-weight: 500;
            line-height: 1.05;
            text-transform: uppercase;
        }
        .home-page .home-deal p {
            position: relative;
            z-index: 1;
            max-width: 260px;
            margin: 0 0 10px;
            font-size: 13px;
            opacity: .92;
        }
        .home-page .home-tag {
            position: relative;
            z-index: 1;
            display: inline-block;
            padding: 5px 12px;
            border-radius: 6px;
            background: #fff;
            color: var(--home-ink);
            font-size: 12px;
            font-weight: 600;
        }

        /* ===== Category tabs ===== */
        .home-page .home-tabs {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 26px;
        }
        .home-page .home-tab {
            padding: 8px 18px;
            border: 1px solid var(--home-line);
            border-radius: 999px;
            background: #fff;
            color: var(--home-ink);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background .2s ease, color .2s ease, border-color .2s ease, transform .2s ease;
        }
        .home-page .home-tab:hover {
            border-color: var(--home-ink);
            transform: translateY(-2px);
        }
        .home-page .home-tab.is-active {
            border-color: var(--home-accent);
            background: var(--home-accent);
            color: #fff;
        }

        /* ===== Subscription cards ===== */
        .home-page .home-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }
        .home-page .home-sub {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--home-line);
            border-radius: 16px;
            background: #fff;
            transition: transform .3s cubic-bezier(.22, 1, .36, 1), box-shadow .3s ease;
        }
        .home-page .home-sub:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 30px rgba(40, 50, 40, .12);
        }
        .home-page .home-sub[hidden] { display: none; }
        .home-page .home-media {
            position: relative;
            display: grid;
            place-items: center;
            height: 132px;
            background: var(--home-tint, #eee);
        }
        .home-page .home-media .home-chip {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 3px 10px;
            border-radius: 999px;
            background: #fff;
            font-size: 11px;
            font-weight: 500;
        }
        .home-page .home-logo-tile {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: #fff;
            font-size: 30px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .1);
            transition: transform .3s ease;
        }
        .home-page .home-sub:hover .home-logo-tile { transform: scale(1.08) rotate(-3deg); }
        .home-page .home-logo-tile img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }
        .home-page .home-category-entertainment { --home-tint: #ffe6da; }
        .home-page .home-category-education     { --home-tint: #e2ecff; }
        .home-page .home-category-creative      { --home-tint: #f1e4ff; }
        .home-page .home-category-productivity  { --home-tint: #dff3e6; }
        .home-page .home-category-research      { --home-tint: #fff0cf; }

        .home-page .home-card-body {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 16px;
        }
        .home-page .home-card-body h3 { margin: 0 0 4px; font-size: 15px; font-weight: 600; }
        .home-page .home-card-body p {
            display: -webkit-box;
            margin: 0 0 14px;
            overflow: hidden;
            color: var(--home-muted);
            font-size: 12.5px;
            line-height: 1.55;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .home-page .home-card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: auto;
            color: var(--home-muted);
            font-size: 12px;
        }
        .home-page .home-card-foot b { color: var(--home-accent-dk); font-size: 15px; }
        .home-page .home-card-foot a {
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--home-ink);
            color: #fff;
            font-weight: 500;
            transition: background .2s ease;
        }
        .home-page .home-card-foot a:hover { background: var(--home-accent-dk); }

        /* ===== How it works ===== */
        .home-page .home-steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        .home-page .home-step .home-step-number {
            margin-bottom: 8px;
            color: var(--home-accent);
            font-family: 'Newsreader', Georgia, serif;
            font-size: 40px;
            line-height: 1;
        }
        .home-page .home-step h3 { margin: 0 0 4px; font-size: 16px; font-weight: 600; }
        .home-page .home-step p { margin: 0; color: var(--home-muted); font-size: 13.5px; }

        /* ===== Footer ===== */
        .home-page .home-footer {
            padding: 36px 0 44px;
            text-align: center;
            color: var(--home-muted);
            font-size: 13px;
        }

        /* ===== Scroll reveal ===== */
        .home-page.home-js [data-home-reveal] {
            opacity: 0;
            transform: translateY(20px);
            transition:
                opacity .8s cubic-bezier(.22, 1, .36, 1),
                transform .8s cubic-bezier(.22, 1, .36, 1);
        }
        .home-page.home-js [data-home-reveal].home-in {
            opacity: 1;
            transform: none;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1000px) {
            .home-page .home-grid,
            .home-page .home-steps { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 760px) {
            .home-page .home-wrap { width: min(100% - 2rem, 1160px); }
            .home-page .home-duo,
            .home-page .home-trio { grid-template-columns: 1fr; }

            .home-page .home-search {
                flex-direction: column;
                align-items: stretch;
                padding: 10px;
                border-radius: 22px;
            }
            .home-page .home-search-seg,
            .home-page .home-search-seg:first-child {
                padding: 8px 14px;
                border-left: 0;
                border-bottom: 1px solid var(--home-line);
            }
            .home-page .home-search-go {
                width: 100%;
                height: 46px;
                margin-top: 8px;
                border-radius: 999px;
            }
        }

        @media (max-width: 520px) {
            .home-page .home-grid,
            .home-page .home-steps { grid-template-columns: 1fr; }
            .home-page .home-head { align-items: flex-start; flex-direction: column; }
        }

        @media (prefers-reduced-motion: reduce) {
            .home-page *,
            .home-page *::before,
            .home-page *::after { transition: none !important; animation: none !important; }
            .home-page.home-js [data-home-reveal] { opacity: 1; transform: none; }
        }
    </style>
</head>

<body class="home-page" data-turbo="false">
<script>document.body.classList.add('home-js');</script>

<?php app_nav('home'); ?>

<main class="home-wrap">

    <!-- Hero -->
    <section class="home-hero">
        <span class="home-greet">Welcome back, <?= e($firstName) ?></span>

        <h1>Find your next shared plan</h1>

        <p>Browse subscriptions other students are splitting, or open a group of your own.</p>

        <form class="home-search" action="/browse.php" method="GET">
            <div class="home-search-seg">
                <label for="home-q">Subscription</label>
                <input id="home-q" name="q" type="text" placeholder="Netflix, Canva, Turnitin...">
            </div>

            <div class="home-search-seg">
                <label for="home-category">Category</label>
                <select id="home-category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>"><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="home-search-seg">
                <label for="home-slots">Open slots</label>
                <select id="home-slots" name="slots">
                    <option value="">Any</option>
                    <option value="1">1+ slot</option>
                    <option value="2">2+ slots</option>
                    <option value="3">3+ slots</option>
                </select>
            </div>

            <button class="home-search-go" type="submit" aria-label="Search">
                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.3-4.3"/>
                </svg>
            </button>
        </form>
    </section>

    <!-- Photo cards -->
    <section class="home-section">
        <div class="home-duo">
            <div class="home-photo home-photo-a" data-home-reveal>
                <div>
                    <h2>Split more.<br>Pay less.</h2>
                    <a href="#home-how" class="home-white-pill">See how it works</a>
                </div>
            </div>

            <div class="home-photo home-photo-b" data-home-reveal style="transition-delay:.1s">
                <div>
                    <h2>Find your group<br>and start sharing.</h2>
                    <a href="/browse.php" class="home-white-pill">Browse subscriptions</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick actions -->
    <section class="home-section">
        <div class="home-head" data-home-reveal>
            <h2>Jump in</h2>
            <a href="/browse.php" class="home-link-accent">View all</a>
        </div>

        <div class="home-trio">
            <a href="/browse.php" class="home-deal home-deal-g1" data-home-reveal>
                <h3>Join a<br>group</h3>
                <div>
                    <p>Take an open slot and pay only your share.</p>
                    <span class="home-tag">Browse groups</span>
                </div>
            </a>

            <a href="/create-group.php" class="home-deal home-deal-g2" data-home-reveal style="transition-delay:.08s">
                <h3>Create a<br>group</h3>
                <div>
                    <p>Already have a plan? Split it with others.</p>
                    <span class="home-tag">Start a group</span>
                </div>
            </a>

            <a href="/dashboard.php" class="home-deal home-deal-g3" data-home-reveal style="transition-delay:.16s">
                <h3>Your<br>subscriptions</h3>
                <div>
                    <p>Check active plans and renewal dates.</p>
                    <span class="home-tag">Open dashboard</span>
                </div>
            </a>
        </div>
    </section>

    <!-- Subscriptions -->
    <section class="home-section" id="home-browse">
        <div class="home-center-head" data-home-reveal>
            <h2>Find the perfect plan<br>to share</h2>
            <p>Streaming, school tools, editing and research, all split with other students.</p>
        </div>

        <div class="home-tabs" id="home-tabs" data-home-reveal>
            <button type="button" class="home-tab is-active" data-home-cat="all">All</button>

            <?php foreach ($categories as $category): ?>
                <button type="button" class="home-tab" data-home-cat="<?= e($category) ?>">
                    <?= e($category) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="home-grid" id="home-grid">
            <?php foreach ($catalog as [$name, $domain, $cat, $fallback, $pct, $desc]): ?>
                <?php $categoryClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($cat))); ?>

                <article
                    class="home-sub home-category-<?= e($categoryClass) ?>"
                    data-home-cat="<?= e($cat) ?>"
                >
                    <div class="home-media">
                        <span class="home-chip"><?= e($cat) ?></span>

                        <span class="home-logo-tile">
                            <img
                                src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128"
                                alt=""
                                loading="lazy"
                                width="38"
                                height="38"
                                onerror="this.replaceWith(document.createTextNode('<?= e($fallback) ?>'))"
                            >
                        </span>
                    </div>

                    <div class="home-card-body">
                        <h3><?= e($name) ?></h3>
                        <p><?= e($desc) ?></p>

                        <div class="home-card-foot">
                            <span>Save up to <b><?= (int) $pct ?>%</b></span>
                            <a href="/browse.php?q=<?= urlencode($name) ?>">View</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="home-more">
            <a href="/browse.php" class="home-link-accent">See all subscriptions</a>
        </p>
    </section>

    <!-- How it works -->
    <section class="home-section" id="home-how">
        <div class="home-center-head" data-home-reveal>
            <h2>How it works</h2>
            <p>From browsing to your first shared plan in a few minutes.</p>
        </div>

        <div class="home-steps">
            <div class="home-step" data-home-reveal>
                <div class="home-step-number">1</div>
                <h3>Browse</h3>
                <p>Pick a category: education, entertainment, editing, productivity or research.</p>
            </div>

            <div class="home-step" data-home-reveal style="transition-delay:.08s">
                <div class="home-step-number">2</div>
                <h3>Join or create</h3>
                <p>Take a slot in an open group, or start your own and invite others.</p>
            </div>

            <div class="home-step" data-home-reveal style="transition-delay:.16s">
                <div class="home-step-number">3</div>
                <h3>Checkout</h3>
                <p>Pay your share plus a small platform fee. Payment is simulated in this prototype.</p>
            </div>

            <div class="home-step" data-home-reveal style="transition-delay:.24s">
                <div class="home-step-number">4</div>
                <h3>Manage</h3>
                <p>See your active subscriptions and renewal dates in your dashboard.</p>
            </div>
        </div>
    </section>

</main>

<footer class="home-footer">
    © <?= date('Y') ?> ShareHub. Student project prototype, so payments are simulated.
</footer>

<script>
(function () {
    'use strict';

    document.body.classList.add('home-page', 'home-js');

    /* ---------- Category tabs ---------- */
    const MAX_CARDS = 8;
    const tabs  = document.querySelectorAll('.home-tab');
    const cards = document.querySelectorAll('#home-grid .home-sub');

    function filter(category) {
        let shown = 0;

        cards.forEach((card) => {
            const matches    = category === 'all' || card.dataset.homeCat === category;
            const shouldShow = matches && shown < MAX_CARDS;

            card.hidden = !shouldShow;

            if (shouldShow) {
                shown++;
            }
        });
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
            filter(tab.dataset.homeCat);
        });
    });

    filter('all');

    /* ---------- Scroll reveal ---------- */
    const revealEls = document.querySelectorAll('[data-home-reveal]');

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('home-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -40px 0px'
        });

        revealEls.forEach((el) => observer.observe(el));
    } else {
        revealEls.forEach((el) => el.classList.add('home-in'));
    }
})();
</script>

</body>
</html>