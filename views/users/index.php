<div class="page-head">
  <div><h1>Pengguna</h1><p class="muted">Kelola akun admin, petugas, dan peminjam.</p></div>
  <div class="actions"><a class="btn btn-primary" href="<?= e(url('users-create')) ?>">Tambah Pengguna</a></div>
</div>
<form method="get" action="index.php" class="toolbar" data-loading-table>
  <input type="hidden" name="route" value="users">
  <div class="field grow"><label class="sr-only" for="q">Cari</label><input id="q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Cari username, nama, atau nomor identitas"></div>
  <select name="role" aria-label="Role" data-autosubmit>
    <option value="">Semua role</option>
    <?php foreach (['admin', 'petugas', 'peminjam'] as $r): ?><option value="<?= e($r) ?>"<?= sel($f['role'], $r) ?>><?= e(label($r)) ?></option><?php endforeach; ?>
  </select>
  <select name="status" aria-label="Status" data-autosubmit>
    <option value="">Semua status</option>
    <?php foreach (['aktif', 'nonaktif'] as $s): ?><option value="<?= e($s) ?>"<?= sel($f['status'], $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-secondary" type="submit">Cari</button>
  <a class="btn btn-ghost" href="<?= e(url('users')) ?>">Reset</a>
</form>
<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Tidak ada pengguna</strong><p>Coba ubah kata kunci atau filter.</p></div>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Nama</th><th data-sort>Jenis / Kelas</th><th data-sort>Identitas</th><th>Kontak</th><th data-sort>Role</th><th data-sort>Status</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $u): ?>
      <tr>
        <td data-label="Nama"><strong><?= e($u['nama_lengkap']) ?></strong><small class="sub"><?= e($u['username']) ?></small></td>
        <td data-label="Jenis / Kelas"><?= e(label($u['jenis_pengguna'])) ?><?= $u['nama_kelas'] ? ' &middot; ' . e($u['nama_kelas']) : '' ?></td>
        <td data-label="Identitas"><?= e($u['nomor_identitas'] ?? '-') ?></td>
        <td data-label="Kontak"><?= e($u['no_hp'] ?? '-') ?><small class="sub"><?= e($u['email'] ?? '') ?></small></td>
        <td data-label="Role"><span class="badge badge-info"><?= e(label($u['role'])) ?></span></td>
        <td data-label="Status"><?= badge($u['status']) ?></td>
        <td data-label="Aksi" class="col-actions">
          <a class="btn btn-outline btn-xs" href="<?= e(url('users-edit', ['id' => $u['id_user']])) ?>">Ubah</a>
          <?php if ((int)$u['id_user'] !== Auth::id()): ?>
          <form method="post" action="<?= e(url('users-delete')) ?>" class="inline" data-confirm="Hapus pengguna <?= e($u['username']) ?>?">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$u['id_user'] ?>">
            <button class="btn btn-danger-outline btn-xs" type="submit">Hapus</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'users', $f) ?>
