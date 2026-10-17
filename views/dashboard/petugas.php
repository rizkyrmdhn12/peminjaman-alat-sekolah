<div class="page-head">
  <div><h1>Dashboard Petugas</h1><p class="muted">Persetujuan dan pemantauan pengembalian.</p></div>
  <div class="actions"><a class="btn btn-primary" href="<?= e(url('peminjaman-offline')) ?>">Peminjaman Offline</a></div>
</div>
<div class="stats stats-5">
  <a class="stat<?= ($cnt['diajukan'] ?? 0) > 0 ? ' stat-attn' : '' ?>" href="<?= e(url('peminjaman', ['status' => 'diajukan'])) ?>"><span>Menunggu persetujuan</span><strong><?= (int)($cnt['diajukan'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('pengembalian')) ?>"><span>Transaksi aktif</span><strong><?= (int)($cnt['dipinjam'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('pengembalian', ['tab' => 'riwayat', 'dari' => date('Y-m-d'), 'sampai' => date('Y-m-d')])) ?>"><span>Pengembalian hari ini</span><strong><?= (int)$kembaliHariIni ?></strong></a>
  <a class="stat" href="<?= e(url('pengembalian', ['status' => 'terlambat'])) ?>"><span>Alat terlambat</span><strong><?= (int)($cnt['terlambat'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('pengembalian', ['status' => 'hilang'])) ?>"><span>Alat hilang</span><strong><?= (int)($cnt['hilang'] ?? 0) ?></strong></a>
</div>
<section class="panel">
  <div class="panel-head"><h2>Perlu persetujuan</h2></div>
  <?php partial('peminjaman/_mini', ['rows' => $pending, 'aksi' => true, 'empty' => 'Tidak ada pengajuan yang menunggu.']); ?>
</section>
<div class="grid-2">
  <section class="panel">
    <div class="panel-head"><h2>Transaksi aktif</h2></div>
    <?php partial('peminjaman/_mini', ['rows' => $aktif, 'aksi' => false, 'empty' => 'Tidak ada transaksi aktif.']); ?>
  </section>
  <section class="panel">
    <div class="panel-head"><h2>Terlambat</h2></div>
    <?php partial('peminjaman/_mini', ['rows' => $terlambat, 'aksi' => false, 'empty' => 'Tidak ada alat yang terlambat.']); ?>
  </section>
</div>
<section class="panel">
  <div class="panel-head"><h2>Hilang (belum ditemukan)</h2></div>
  <?php partial('peminjaman/_mini', ['rows' => $hilang, 'aksi' => false, 'empty' => 'Tidak ada alat yang hilang.']); ?>
</section>
