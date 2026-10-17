<?php
$tabs = ['aktif' => 'Menunggu dikembalikan', 'riwayat' => 'Riwayat pengembalian', 'denda' => 'Denda'];
?>
<div class="page-head">
  <div><h1>Pengembalian</h1><p class="muted">Pantau alat yang belum kembali dan catat pengembalian. Denda belum selesai: <strong><?= e(rupiah($ringkas['total'])) ?></strong> (<?= (int)$ringkas['jumlah'] ?>)</p></div>
</div>
<nav class="tabs" aria-label="Bagian pengembalian">
  <?php foreach ($tabs as $k => $t): ?><a class="tab<?= $tab === $k ? ' active' : '' ?>" href="<?= e(url('pengembalian', ['tab' => $k])) ?>"><?= e($t) ?></a><?php endforeach; ?>
</nav>
<form method="get" action="index.php" class="toolbar" data-loading-table>
  <input type="hidden" name="route" value="pengembalian"><input type="hidden" name="tab" value="<?= e($tab) ?>">
  <div class="field grow"><label class="sr-only" for="q">Cari</label><input id="q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="<?= $tab === 'denda' ? 'Cari kode, peminjam, atau alat' : 'Cari kode atau nama peminjam' ?>"></div>
  <?php if ($tab === 'aktif'): ?>
    <select name="status" aria-label="Status" data-autosubmit>
      <option value="">Semua status aktif</option>
      <?php foreach (PeminjamanModel::AKTIF as $s): ?><option value="<?= e($s) ?>"<?= sel($f['status'], $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
    </select>
  <?php elseif ($tab === 'riwayat'): ?>
    <input type="date" name="dari" value="<?= e($f['dari']) ?>" aria-label="Dari tanggal" data-autosubmit>
    <input type="date" name="sampai" value="<?= e($f['sampai']) ?>" aria-label="Sampai tanggal" data-autosubmit>
  <?php else: ?>
    <select name="status" aria-label="Status denda" data-autosubmit>
      <option value="">Semua status</option>
      <option value="belum_diselesaikan"<?= sel($f['status'], 'belum_diselesaikan') ?>>Belum diselesaikan</option>
      <option value="diselesaikan"<?= sel($f['status'], 'diselesaikan') ?>>Diselesaikan</option>
    </select>
    <select name="jenis" aria-label="Jenis denda" data-autosubmit>
      <option value="">Semua jenis</option>
      <?php foreach (['keterlambatan', 'kerusakan', 'kehilangan'] as $j): ?><option value="<?= e($j) ?>"<?= sel($f['jenis'], $j) ?>><?= e(label($j)) ?></option><?php endforeach; ?>
    </select>
  <?php endif; ?>
  <button class="btn btn-secondary" type="submit">Cari</button>
  <a class="btn btn-ghost" href="<?= e(url('pengembalian', ['tab' => $tab])) ?>">Reset</a>
</form>

<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Tidak ada data</strong><p><?= $tab === 'aktif' ? 'Tidak ada transaksi yang menunggu pengembalian.' : 'Coba ubah kata kunci atau filter.' ?></p></div>
<?php elseif ($tab === 'aktif'): ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Kode</th><th data-sort>Peminjam</th><th>Alat</th><th data-sort>Batas kembali</th><th data-sort>Status</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $r): ?>
      <tr>
        <td data-label="Kode"><a href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>"><?= e($r['kode_peminjaman']) ?></a></td>
        <td data-label="Peminjam"><?= e($r['nama_peminjam']) ?></td>
        <td data-label="Alat"><?= e($r['daftar_alat']) ?></td>
        <td data-label="Batas kembali" data-value="<?= e($r['batas_kembali']) ?>"><?= e(tgl($r['batas_kembali'], true)) ?></td>
        <td data-label="Status"><?= badge($r['status_peminjaman']) ?></td>
        <td data-label="Aksi" class="col-actions"><a class="btn btn-primary btn-xs" href="<?= e(url('pengembalian-create', ['id' => $r['id_peminjaman']])) ?>">Proses kembali</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php elseif ($tab === 'riwayat'): ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Kode</th><th data-sort>Peminjam</th><th data-sort>Dikembalikan</th><th>Alat</th><th data-sort>Total denda</th><th>Petugas</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $r): ?>
      <tr>
        <td data-label="Kode"><a href="<?= e(url('pengembalian-detail', ['id' => $r['id_pengembalian']])) ?>"><?= e($r['kode_peminjaman']) ?></a></td>
        <td data-label="Peminjam"><?= e($r['nama_peminjam']) ?></td>
        <td data-label="Dikembalikan" data-value="<?= e($r['tanggal_pengembalian']) ?>"><?= e(tgl($r['tanggal_pengembalian'], true)) ?></td>
        <td data-label="Alat"><?= (int)$r['jumlah_alat'] ?> unit</td>
        <td data-label="Total denda" data-value="<?= e($r['total_denda']) ?>"><?= (float)$r['total_denda'] > 0 ? '<strong>' . e(rupiah($r['total_denda'])) . '</strong>' : '-' ?></td>
        <td data-label="Petugas"><?= e($r['nama_petugas'] ?? '-') ?></td>
        <td data-label="Aksi" class="col-actions"><a class="btn btn-outline btn-xs" href="<?= e(url('pengembalian-detail', ['id' => $r['id_pengembalian']])) ?>">Detail</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Tanggal</th><th data-sort>Jenis</th><th>Alat</th><th data-sort>Peminjam</th><th data-sort>Jumlah</th><th data-sort>Status</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $d): ?>
      <tr>
        <td data-label="Tanggal" data-value="<?= e($d['tanggal_denda']) ?>"><?= e(tgl($d['tanggal_denda'], true)) ?></td>
        <td data-label="Jenis"><?= e(label($d['jenis_denda'])) ?><small class="sub"><?= e($d['keterangan'] ?? '') ?></small></td>
        <td data-label="Alat"><?= e($d['kode_inventaris']) ?> &middot; <?= e($d['nama_alat']) ?></td>
        <td data-label="Peminjam"><a href="<?= e(url('peminjaman-detail', ['id' => $d['id_peminjaman']])) ?>"><?= e($d['nama_peminjam']) ?></a><small class="sub"><?= e($d['kode_peminjaman']) ?></small></td>
        <td data-label="Jumlah" data-value="<?= e($d['jumlah_denda']) ?>"><?= e(rupiah($d['jumlah_denda'])) ?></td>
        <td data-label="Status"><?= badge($d['status_denda']) ?></td>
        <td data-label="Aksi" class="col-actions">
          <?php if ($d['status_denda'] === 'belum_diselesaikan' && Auth::can('denda.update')): ?>
          <form method="post" action="<?= e(url('denda-selesai')) ?>" class="inline" data-confirm="Tandai denda ini sudah diselesaikan?">
            <?= Csrf::field() ?><input type="hidden" name="id_denda" value="<?= (int)$d['id_denda'] ?>"><button class="btn btn-outline btn-xs" type="submit">Tandai selesai</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'pengembalian', $f + ['tab' => $tab]) ?>
