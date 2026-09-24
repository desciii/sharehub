<?php
// includes/nav.php — shared ShareHub header bar.
// Head assets: call app_head_assets() inside <head> on every page.
// Nav: call app_nav('browse') right after <body>. Keys: home, browse, create, dashboard

require_once __DIR__ . '/auth.php';

function app_head_assets(): void
{
    ?>
<meta name="view-transition" content="same-origin">
<link rel="stylesheet" href="/assets/nav.css?v=3">
<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
<script src="/js/nav.js" defer></script>
<?php
}

function app_nav(string $active = ''): void
{
    $user      = current_user();
    $firstName = explode(' ', trim($user['name'] ?? ''))[0] ?: 'there';
    $initial   = strtoupper(substr($firstName, 0, 1));
    $on = static fn(string $key): string => $active === $key ? ' class="on"' : '';
    ?>
<header class="sh-nav" id="nav">
  <div class="sh-nav__in">
    <a href="/home.php" class="sh-nav__logo">ShareHub</a>
    <nav class="sh-nav__links">
      <a href="/browse.php"<?= $on('browse') ?>>Browse subscriptions</a>
      <a href="/create-group.php"<?= $on('create') ?>>Create a group</a>
      <a href="/dashboard.php"<?= $on('dashboard') ?>>My subscriptions</a>
      <a href="/home.php#how">How it works</a>
    </nav>
    <div class="sh-nav__right">
      <a href="/create-group.php" class="sh-nav__pill">Create a group</a>
      <div class="sh-nav__user">
        <span class="sh-nav__avatar"><?= htmlspecialchars($initial) ?></span>
        <span class="sh-nav__nm"><?= htmlspecialchars($firstName) ?></span>
      </div>
      <form method="POST" action="/auth-handler.php" class="sh-nav__logout" data-turbo="false">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="logout">
        <button type="submit">Log out</button>
      </form>
    </div>
  </div>
</header>
<?php
}