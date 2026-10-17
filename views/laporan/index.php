<?php
$jenisLabel = ['peminjaman' => 'Peminjaman', 'pengembalian' => 'Pengembalian', 'denda' => 'Denda'];
$qs = array_filter($f, fn($v) => $v !== '');
?>
<div class="page-head">
  <div><h1>Laporan</h1><p class="muted">Rekap transaksi dengan filter tanggal, status, jenis, peminjam, alat, dan kategori.</p></div>
  <div class="actions"><a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e(url('laporan-cetak', $qs)) ?>">Cetak</a></div>
</div>
<nav class="tabs" aria-label="Jenis laporan">
  <?php foreach ($jenisLabel as $k => $t): ?><a class="tab<?= $f['jenis'] === $k ? ' active' : '' ?>" href="<?= e(url('laporan', ['jenis' => $k])) ?>"><?= e($t) ?></a><?php endforeach; ?>
</nav>
<form method="get" action="index.php" class="toolbar toolbar-wrap" data-loading-table>
  <input type="hidden" name="route" value="laporan"><input type="hidden" name="jenis" value="<?= e($f['jenis']) ?>">
  <input type="date" name="dari" value="<?= e($f['dari']) ?>" aria-label="Dari tanggal" data-autosubmit>
  <input type="date" name="sampai" value="<?= e($f['sampai']) ?>" aria-label="Sampai tanggal" data-autosubmit>
  <?php if ($statusList): ?>
  <select name="status" aria-label="Status" data-autosubmit>
    <option value="">Semua status</option>
    <?php foreach ($statusList as $s): ?><option value="<?= e($s) ?>"<?= sel($f['status'], $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
  </select>
  <?php endif; ?>
  <?php if ($f['jenis'] !== 'denda'): ?>
  <select name="jenis_peminjaman" aria-label="Jenis peminjaman" data-autosubmit>
    <option value="">Online &amp; offline</option>
    <option value="online"<?= sel($f['jenis_peminjaman'], 'online') ?>>Online</option>
    <option value="offline"<?= sel($f['jenis_peminjaman'], 'offline') ?>>Offline</option>
  </select>
  <?php else: ?>
  <select name="jenis_denda" aria-label="Jenis denda" data-autosubmit>
    <option value="">Semua jenis denda</option>
    <?php foreach (['keterlambatan', 'kerusakan', 'kehilangan'] as $j): ?><option value="<?= e($j) ?>"<?= sel($f['jenis_denda'], $j) ?>><?= e(label($j)) ?></option><?php endforeach; ?>
  </select>
  <?php endif; ?>
  <div class="field grow"><label class="sr-only" for="peminjam">Peminjam</label><input id="peminjam" type="search" name="peminjam" value="<?= e($f['peminjam']) ?>" placeholder="Cari peminjam"></div>
  <select name="id_kategori" aria-label="Kategori" data-autosubmit>
    <option value="">Semua kategori</option>
    <?php foreach ($kategori as $k): ?><option value="<?= (int)$k['id_kategori'] ?>"<?= sel($f['id_kategori'], $k['id_kategori']) ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?>
  </select>
  <select name="id_alat" aria-label="Alat" data-autosubmit>
    <option value="">Semua alat</option>
    <?php foreach ($alatList as $a): ?><option value="<?= (int)$a['id_alat'] ?>"<?= sel($f['id_alat'], $a['id_alat']) ?>><?= e($a['kode_inventaris']) ?> - <?= e($a['nama_alat']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-secondary" type="submit">Terapkan</button>
  <a class="btn btn-ghost" href="<?= e(url('laporan', ['jenis' => $f['jenis']])) ?>">Reset</a>
</form>

<?php if ($pg['total_denda'] !== null): ?><p class="muted">Total <?= $f['jenis'] === 'denda' ? 'denda' : 'denda pada rentang ini' ?>: <strong><?= e(rupiah($pg['total_denda'])) ?></strong> dari <?= (int)$pg['total'] ?> data.</p><?php endif; ?>

<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Tidak ada data</strong><p>Coba ubah rentang tanggal atau filter.</p></div>
<?php elseif ($f['jenis'] === 'peminjaman'): ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Kode</th><th data-sort>Peminjam</th><th>Alat</th><th data-sort>Waktu mulai</th><th data-sort>Batas kembali</th><th data-sort>Jenis</th><th data-sort>Status</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $r): ?>
      <tr>
        <td data-label="Kode"><a href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>"><?= e($r['kode_peminjaman']) ?></a></td>
        <td data-label="Peminjam"><?= e($r['nama_peminjam']) ?></td>
        <td data-label="Alat"><?= e($r['daftar_alat']) ?></td>
        <td data-label="Waktu mulai" data-value="<?= e($r['waktu_mulai']) ?>"><?= e(tgl($r['waktu_mulai'], true)) ?></td>
        <td data-label="Batas kembali" data-value="<?= e($r['batas_kembali']) ?>"><?= e(tgl($r['batas_kembali'], true)) ?></td>
        <td data-label="Jenis"><?= badge($r['jenis_peminjaman']) ?></td>
        <td data-label="Status"><?= badge($r['status_peminjaman']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php elseif ($f['jenis'] === 'pengembalian'): ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Kode</th><th data-sort>Peminjam</th><th>Alat</th><th data-sort>Dikembalikan</th><th data-sort>Jenis</th><th data-sort>Total denda</th><th>Petugas</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $r): ?>
      <tr>
        <td data-label="Kode"><?= e($r['kode_peminjaman']) ?></td>
        <td data-label="Peminjam"><?= e($r['nama_peminjam']) ?></td>
        <td data-label="Alat"><?= e($r['daftar_alat']) ?></td>
        <td data-label="Dikembalikan" data-value="<?= e($r['tanggal_pengembalian']) ?>"><?= e(tgl($r['tanggal_pengembalian'], true)) ?></td>
        <td data-label="Jenis"><?= badge($r['jenis_peminjaman']) ?></td>
        <td data-label="Total denda" data-value="<?= e($r['total_denda']) ?>"><?= e(rupiah($r['total_denda'])) ?></td>
        <td data-label="Petugas"><?= e($r['nama_petugas'] ?? '-') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Tanggal</th><th data-sort>Jenis</th><th>Alat</th><th data-sort>Peminjam</th><th data-sort>Jumlah</th><th data-sort>Status</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $r): ?>
      <tr>
        <td data-label="Tanggal" data-value="<?= e($r['tanggal_denda']) ?>"><?= e(tgl($r['tanggal_denda'], true)) ?></td>
        <td data-label="Jenis"><?= e(label($r['jenis_denda'])) ?></td>
        <td data-label="Alat"><?= e($r['kode_inventaris']) ?> &middot; <?= e($r['nama_alat']) ?></td>
        <td data-label="Peminjam"><?= e($r['nama_peminjam']) ?><small class="sub"><?= e($r['kode_peminjaman']) ?></small></td>
        <td data-label="Jumlah" data-value="<?= e($r['jumlah_denda']) ?>"><?= e(rupiah($r['jumlah_denda'])) ?></td>
        <td data-label="Status"><?= badge($r['status_denda']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'laporan', $f) ?>
