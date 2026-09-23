<?php
// includes/sidebar.php
// Expects an optional $activePage variable (e.g. 'home', 'browse') set before including.
$activePage = $activePage ?? '';
$userName = $_SESSION['user_name'] ?? 'Guest';
$userRole = $_SESSION['user_role'] ?? null;

$navItems = [
    'home'          => ['label' => '⌂ &nbsp; Home',       'href' => '/index.php'],
    'browse'        => ['label' => '▦ &nbsp; Browse',      'href' => '/browse.php'],
    'dashboard'     => ['label' => '◉ &nbsp; My Dashboard','href' => '/dashboard.php'],
];
if ($userRole === 'admin') {
    $navItems['admin'] = ['label' => '▥ &nbsp; Admin Dashboard', 'href' => '/admin.php'];
}
?>
<aside class="sidebar">
  <div class="brand"><div class="logo">S</div> ShareHub</div>
  <nav class="nav">
    <?php foreach ($navItems as $key => $item): ?>
      <a href="<?= $item['href'] ?>" class="<?= $activePage === $key ? 'active' : '' ?>" style="text-decoration:none;display:block">
        <button class="<?= $activePage === $key ? 'active' : '' ?>"><?= $item['label'] ?></button>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-bottom">
    <div class="user-mini">
      <b><?= htmlspecialchars($userName) ?></b><br>
      <span class="muted"><?= $userRole ? htmlspecialchars(ucfirst($userRole)) . ' account' : 'Not logged in' ?></span>
    </div>
  </div>
</aside>
