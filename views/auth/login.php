<section class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <img src="<?= e(asset('images/logo.svg')) ?>" alt="" width="44" height="44">
      <h1>Masuk</h1>
      <p class="muted">Gunakan akun <?= e(APP_NAME) ?> Anda.</p>
    </div>
    <form method="post" action="<?= e(url('login')) ?>" data-validate data-loading novalidate>
      <?= Csrf::field() ?>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" maxlength="30" autocomplete="username" required autofocus value="<?= e(Flash::old('username', '')) ?>">
        <div class="field-error" data-error-for="username"></div>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <div class="pw-wrap">
          <input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
          <button type="button" class="pw-toggle" data-toggle-password aria-label="Tampilkan password">Lihat</button>
        </div>
        <div class="field-error" data-error-for="password"></div>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><span class="btn-label">Masuk</span></button>
    </form>
    <p class="auth-foot">Belum punya akun? <a href="<?= e(url('register')) ?>">Daftar sebagai peminjam</a></p>
  </div>
</section>
