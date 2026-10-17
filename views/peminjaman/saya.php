<div class="page-head">
  <div><h1>Peminjaman Saya</h1><p class="muted">Riwayat dan status transaksi milik Anda.</p></div>
  <div class="actions"><a class="btn btn-primary" href="<?= e(url('peminjaman-create')) ?>">Ajukan Peminjaman</a></div>
</div>
<form method="get" action="index.php" class="toolbar" data-loading-table>
  <input type="hidden" name="route" value="peminjaman-saya">
  <select name="status" aria-label="Status" data-autosubmit>
    <option value="">Semua status</option>
    <?php foreach ($status as $s): ?><option value="<?= e($s) ?>"<?= sel($f['status'], $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
  </select>
  <input type="date" name="tanggal" value="<?= e($f['tanggal']) ?>" aria-label="Tanggal" data-autosubmit>
  <button class="btn btn-secondary" type="submit">Terapkan</button>
  <a class="btn btn-ghost" href="<?= e(url('peminjaman-saya')) ?>">Reset</a>
</form>
<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Belum ada transaksi</strong><p>Ajukan peminjaman pertama Anda untuk hari ini.</p><a class="btn btn-primary" href="<?= e(url('peminjaman-create')) ?>">Ajukan Peminjaman</a></div>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Kode</th><th>Alat</th><th data-sort>Waktu</th><th data-sort>Status</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $r): ?>
      <tr>
        <td data-label="Kode"><a href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>"><?= e($r['kode_peminjaman']) ?></a></td>
        <td data-label="Alat"><?= e($r['daftar_alat']) ?></td>
        <td data-label="Waktu" data-value="<?= e($r['waktu_mulai']) ?>"><?= e(tgl($r['tanggal_peminjaman'])) ?><small class="sub"><?= e(substr($r['waktu_mulai'], 11, 5)) ?> - <?= e(substr($r['batas_kembali'], 11, 5)) ?></small></td>
        <td data-label="Status"><?= badge($r['status_peminjaman']) ?></td>
        <td data-label="Aksi" class="col-actions">
          <a class="btn btn-outline btn-xs" href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>">Detail</a>
          <?php if ($r['status_peminjaman'] === 'diajukan'): ?>
          <form method="post" action="<?= e(url('peminjaman-cancel-own')) ?>" class="inline" data-confirm="Batalkan pengajuan ini?">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id_peminjaman'] ?>">
            <button class="btn btn-danger-outline btn-xs" type="submit">Batalkan</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'peminjaman-saya', $f) ?>
