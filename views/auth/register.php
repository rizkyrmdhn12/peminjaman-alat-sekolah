<section class="auth-wrap">
  <div class="auth-card auth-card-wide">
    <div class="auth-head">
      <img src="<?= e(asset('images/logo.svg')) ?>" alt="" width="44" height="44">
      <h1>Daftar Peminjam</h1>
      <p class="muted">Untuk siswa, guru, dan staff <?= e(APP_SCHOOL) ?>.</p>
    </div>
    <form method="post" action="<?= e(url('register')) ?>" data-validate data-loading data-user-form novalidate>
      <?= Csrf::field() ?>
      <div class="form-grid">
        <div class="field">
          <label for="username">Username</label>
          <input id="username" name="username" maxlength="30" autocomplete="username" required value="<?= e(fv('username')) ?>">
          <?= ferr('username') ?>
        </div>
        <div class="field">
          <label for="nama_lengkap">Nama lengkap</label>
          <input id="nama_lengkap" name="nama_lengkap" maxlength="100" required value="<?= e(fv('nama_lengkap')) ?>">
          <?= ferr('nama_lengkap') ?>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="pw-wrap">
            <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
            <button type="button" class="pw-toggle" data-toggle-password aria-label="Tampilkan password">Lihat</button>
          </div>
          <?= ferr('password') ?>
          <div class="field-hint">Minimal 8 karakter.</div>
        </div>
        <div class="field">
          <label for="password_confirm">Konfirmasi password</label>
          <input id="password_confirm" name="password_confirm" type="password" maxlength="72" autocomplete="new-password" required>
          <?= ferr('password_confirm') ?>
        </div>
        <div class="field">
          <label for="jenis_pengguna">Jenis pengguna</label>
          <select id="jenis_pengguna" name="jenis_pengguna" required>
            <option value="">Pilih</option>
            <?php foreach (['siswa' => 'Siswa', 'guru' => 'Guru', 'staff' => 'Staff'] as $v => $t): ?>
              <option value="<?= e($v) ?>"<?= sel(fv('jenis_pengguna'), $v) ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
          <?= ferr('jenis_pengguna') ?>
        </div>
        <div class="field" id="field-kelas" hidden>
          <label for="id_kelas">Kelas</label>
          <select id="id_kelas" name="id_kelas">
            <option value="">Pilih kelas</option>
            <?php foreach ($kelas as $k): ?>
              <option value="<?= (int)$k['id_kelas'] ?>"<?= sel(fv('id_kelas'), $k['id_kelas']) ?>><?= e($k['nama_kelas']) ?></option>
            <?php endforeach; ?>
          </select>
          <?= ferr('id_kelas') ?>
        </div>
        <div class="field">
          <label for="nomor_identitas">NIS / NIP / nomor identitas</label>
          <input id="nomor_identitas" name="nomor_identitas" maxlength="30" required value="<?= e(fv('nomor_identitas')) ?>">
          <?= ferr('nomor_identitas') ?>
        </div>
        <div class="field">
          <label for="no_hp">Nomor HP <span class="opt">(opsional)</span></label>
          <input id="no_hp" name="no_hp" type="tel" maxlength="20" value="<?= e(fv('no_hp')) ?>">
          <?= ferr('no_hp') ?>
        </div>
        <div class="field">
          <label for="email">Email <span class="opt">(opsional)</span></label>
          <input id="email" name="email" type="email" maxlength="100" value="<?= e(fv('email')) ?>">
          <?= ferr('email') ?>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><span class="btn-label">Buat Akun</span></button>
    </form>
    <p class="auth-foot">Sudah punya akun? <a href="<?= e(url('login')) ?>">Login</a></p>
  </div>
</section>
