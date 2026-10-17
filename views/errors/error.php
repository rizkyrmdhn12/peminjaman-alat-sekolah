<div class="error-page">
  <div class="error-code"><?= e($code) ?></div>
  <h1><?= e($title) ?></h1>
  <p><?= e($message) ?></p>
  <div class="actions center">
    <?php if ($code === 401): ?>
      <a class="btn btn-primary" href="<?= e(url('login')) ?>">Login</a>
      <a class="btn btn-ghost" href="<?= e(url('home')) ?>">Beranda</a>
    <?php elseif (Auth::check()): ?>
      <a class="btn btn-primary" href="<?= e(url(Auth::homeRoute())) ?>">Ke Dashboard</a>
    <?php else: ?>
      <a class="btn btn-primary" href="<?= e(url('home')) ?>">Ke Beranda</a>
    <?php endif; ?>
  </div>
</div>
