<div class="page-head">
  <div><h1>Peminjaman</h1><p class="muted">Seluruh transaksi peminjaman alat.</p></div>
  <div class="actions"><a class="btn btn-primary" href="<?= e(url('peminjaman-offline')) ?>">Peminjaman Offline</a></div>
</div>
<form method="get" action="index.php" class="toolbar" data-loading-table>
  <input type="hidden" name="route" value="peminjaman">
  <div class="field grow"><label class="sr-only" for="q">Cari</label><input id="q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Cari kode atau nama peminjam"></div>
  <select name="status" aria-label="Status" data-autosubmit>
    <option value="">Semua status</option>
    <?php foreach ($status as $s): ?><option value="<?= e($s) ?>"<?= sel($f['status'], $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
  </select>
  <select name="jenis" aria-label="Jenis" data-autosubmit>
    <option value="">Online &amp; offline</option>
    <option value="online"<?= sel($f['jenis'], 'online') ?>>Online</option>
    <option value="offline"<?= sel($f['jenis'], 'offline') ?>>Offline</option>
  </select>
  <input type="date" name="tanggal" value="<?= e($f['tanggal']) ?>" aria-label="Tanggal" data-autosubmit>
  <button class="btn btn-secondary" type="submit">Cari</button>
  <a class="btn btn-ghost" href="<?= e(url('peminjaman')) ?>">Reset</a>
</form>
<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Tidak ada transaksi</strong><p>Coba ubah kata kunci atau filter.</p></div>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Kode</th><th data-sort>Peminjam</th><th>Alat</th><th data-sort>Waktu</th><th data-sort>Jenis</th><th data-sort>Status</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $r): ?>
      <tr>
        <td data-label="Kode"><a href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>"><?= e($r['kode_peminjaman']) ?></a></td>
        <td data-label="Peminjam"><?= e($r['nama_peminjam']) ?></td>
        <td data-label="Alat"><?= e($r['daftar_alat']) ?></td>
        <td data-label="Waktu" data-value="<?= e($r['waktu_mulai']) ?>"><?= e(tgl($r['tanggal_peminjaman'])) ?><small class="sub"><?= e(substr($r['waktu_mulai'], 11, 5)) ?> - <?= e(substr($r['batas_kembali'], 11, 5)) ?></small></td>
        <td data-label="Jenis"><?= badge($r['jenis_peminjaman']) ?></td>
        <td data-label="Status"><?= badge($r['status_peminjaman']) ?></td>
        <td data-label="Aksi" class="col-actions">
          <?php if ($r['status_peminjaman'] === 'diajukan'): ?>
            <?php if (Auth::can('peminjaman.approve')): ?>
            <form method="post" action="<?= e(url('peminjaman-approve')) ?>" class="inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id_peminjaman'] ?>"><input type="hidden" name="next" value="list"><button class="btn btn-primary btn-xs" type="submit">Setujui</button></form>
            <?php endif; ?>
            <?php if (Auth::can('peminjaman.reject')): ?>
            <form method="post" action="<?= e(url('peminjaman-reject')) ?>" class="inline" data-confirm="Tolak pengajuan ini?"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id_peminjaman'] ?>"><input type="hidden" name="next" value="list"><button class="btn btn-outline btn-xs" type="submit">Tolak</button></form>
            <?php endif; ?>
          <?php elseif (in_array($r['status_peminjaman'], PeminjamanModel::AKTIF, true) && Auth::can('pengembalian.create')): ?>
            <a class="btn btn-primary btn-xs" href="<?= e(url('pengembalian-create', ['id' => $r['id_peminjaman']])) ?>">Proses kembali</a>
          <?php endif; ?>
          <a class="btn btn-outline btn-xs" href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>">Detail</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'peminjaman', $f) ?>
