<?php $flash = Flash::pull(); $logged = Auth::check(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="Sistem peminjaman alat multimedia dan presentasi <?= e(APP_SCHOOL) ?>.">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/logo.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="public">
<header class="site-header">
  <div class="container site-header-inner">
    <a class="brand brand-light" href="<?= e(url('home')) ?>">
      <img src="<?= e(asset('images/logo.svg')) ?>" alt="" width="34" height="34">
      <span><strong>Sangkuriang</strong> Equipment</span>
    </a>
    <nav class="site-nav" id="siteNav" aria-label="Menu">
      <a href="<?= e(url('home')) ?>#fitur">Fitur</a>
      <a href="<?= e(url('home')) ?>#cara-kerja">Cara Kerja</a>
      <a href="<?= e(url('home')) ?>#alat">Alat</a>
    </nav>
    <div class="site-actions">
      <?php if ($logged): ?>
        <a class="btn btn-primary btn-sm" href="<?= e(url(Auth::homeRoute())) ?>">Dashboard</a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('login')) ?>">Login</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('register')) ?>">Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main id="content"><?= $content ?></main>
<footer class="site-footer">
  <div class="container">
    <span><strong><?= e(APP_NAME) ?></strong> &middot; <?= e(APP_SCHOOL) ?></span>
    <span>Sistem peminjaman alat sekolah</span>
  </div>
</footer>
<div class="toasts" id="toasts" aria-live="polite">
  <?php foreach ($flash as $f): ?>
    <div class="toast toast-<?= e($f['type']) ?>" role="status"><span><?= e($f['msg']) ?></span><button type="button" class="toast-close" aria-label="Tutup">&times;</button></div>
  <?php endforeach; ?>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
