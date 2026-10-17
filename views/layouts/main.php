<?php
$route = (string)($_GET['route'] ?? '');
$menu = (require BASE_PATH . '/config/menu.php')[$auth['role']] ?? [];
$flash = Flash::pull();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/logo.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="app">
<div class="scrim" id="scrim"></div>
<aside class="sidebar" id="sidebar" aria-label="Navigasi">
  <a class="brand" href="<?= e(url('home')) ?>">
    <img src="<?= e(asset('images/logo.svg')) ?>" alt="" width="34" height="34">
    <span><strong>Sangkuriang</strong> Equipment</span>
  </a>
  <nav class="nav">
    <?php foreach ($menu as [$lbl, $r, $prefixes]):
        $active = false;
        foreach ($prefixes as $pf) { if (strpos($route, $pf) === 0) { $active = true; } } ?>
      <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= e(url($r)) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($lbl) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <div class="whoami"><strong><?= e($auth['nama_lengkap']) ?></strong><span><?= e(label($auth['role'])) ?></span></div>
    <form method="post" action="<?= e(url('logout')) ?>">
      <?= Csrf::field() ?>
      <button type="submit" class="nav-link nav-logout">Logout</button>
    </form>
  </div>
</aside>
<div class="main">
  <header class="topbar">
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false"><span></span><span></span><span></span></button>
    <span class="topbar-school"><?= e(APP_SCHOOL) ?></span>
    <span class="topbar-date"><?= e(JadwalService::hariIni() ?? 'Akhir pekan') ?>, <?= e(tgl(date('Y-m-d'))) ?></span>
  </header>
  <main class="content" id="content">
    <?= $content ?>
  </main>
</div>
<div class="toasts" id="toasts" aria-live="polite">
  <?php foreach ($flash as $f): ?>
    <div class="toast toast-<?= e($f['type']) ?>" role="status"><span><?= e($f['msg']) ?></span><button type="button" class="toast-close" aria-label="Tutup">&times;</button></div>
  <?php endforeach; ?>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
