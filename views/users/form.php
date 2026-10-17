<?php $isEdit = $row !== null; ?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1></div>
  <div class="actions"><a class="btn btn-ghost" href="<?= e(url('users')) ?>">Kembali</a></div>
</div>
<form method="post" action="<?= e(url($action)) ?>" class="panel form" data-validate data-loading data-user-form novalidate>
  <?= Csrf::field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$row['id_user'] ?>"><?php endif; ?>
  <div class="form-grid">
    <div class="field"><label for="username">Username</label><input id="username" name="username" maxlength="30" required value="<?= e(fv('username', $row)) ?>"><?= ferr('username') ?></div>
    <div class="field"><label for="nama_lengkap">Nama lengkap</label><input id="nama_lengkap" name="nama_lengkap" maxlength="100" required value="<?= e(fv('nama_lengkap', $row)) ?>"><?= ferr('nama_lengkap') ?></div>
    <div class="field">
      <label for="password">Password <?= $isEdit ? '<span class="opt">(kosongkan jika tidak diubah)</span>' : '' ?></label>
      <div class="pw-wrap"><input id="password" name="password" type="password" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>><button type="button" class="pw-toggle" data-toggle-password aria-label="Tampilkan password">Lihat</button></div>
      <?= ferr('password') ?>
    </div>
    <div class="field"><label for="password_confirm">Konfirmasi password</label><input id="password_confirm" name="password_confirm" type="password" maxlength="72" autocomplete="new-password"><?= ferr('password_confirm') ?></div>
    <div class="field">
      <label for="role">Role</label>
      <select id="role" name="role" required>
        <?php foreach (['admin', 'petugas', 'peminjam'] as $r): ?><option value="<?= e($r) ?>"<?= sel(fv('role', $row, 'peminjam'), $r) ?>><?= e(label($r)) ?></option><?php endforeach; ?>
      </select>
      <?= ferr('role') ?>
      <?php if ($self): ?><div class="field-hint">Role dan status akun sendiri tidak dapat diubah.</div><?php endif; ?>
    </div>
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" required>
        <?php foreach (['aktif', 'nonaktif'] as $s): ?><option value="<?= e($s) ?>"<?= sel(fv('status', $row, 'aktif'), $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
      </select>
      <?= ferr('status') ?>
    </div>
    <div class="field">
      <label for="jenis_pengguna">Jenis pengguna</label>
      <select id="jenis_pengguna" name="jenis_pengguna">
        <option value="">-</option>
        <?php foreach (['siswa' => 'Siswa', 'guru' => 'Guru', 'staff' => 'Staff'] as $v => $t): ?><option value="<?= e($v) ?>"<?= sel(fv('jenis_pengguna', $row), $v) ?>><?= e($t) ?></option><?php endforeach; ?>
      </select>
      <?= ferr('jenis_pengguna') ?>
    </div>
    <div class="field" id="field-kelas" hidden>
      <label for="id_kelas">Kelas</label>
      <select id="id_kelas" name="id_kelas">
        <option value="">Pilih kelas</option>
        <?php foreach ($kelas as $k): ?><option value="<?= (int)$k['id_kelas'] ?>"<?= sel(fv('id_kelas', $row), $k['id_kelas']) ?>><?= e($k['nama_kelas']) ?></option><?php endforeach; ?>
      </select>
      <?= ferr('id_kelas') ?>
    </div>
    <div class="field"><label for="nomor_identitas">Nomor identitas (NIS/NIP)</label><input id="nomor_identitas" name="nomor_identitas" maxlength="30" value="<?= e(fv('nomor_identitas', $row)) ?>"><?= ferr('nomor_identitas') ?></div>
    <div class="field"><label for="no_hp">Nomor HP</label><input id="no_hp" name="no_hp" type="tel" maxlength="20" value="<?= e(fv('no_hp', $row)) ?>"><?= ferr('no_hp') ?></div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" value="<?= e(fv('email', $row)) ?>"><?= ferr('email') ?></div>
  </div>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><span class="btn-label">Simpan</span></button><a class="btn btn-ghost" href="<?= e(url('users')) ?>">Batal</a></div>
</form>
