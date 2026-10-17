<div class="page-head">
  <div><h1>Dashboard Admin</h1><p class="muted">Ringkasan operasional peminjaman alat.</p></div>
  <div class="actions"><a class="btn btn-primary" href="<?= e(url('peminjaman-offline')) ?>">Peminjaman Offline</a></div>
</div>
<div class="stats">
  <a class="stat<?= ($cnt['diajukan'] ?? 0) > 0 ? ' stat-attn' : '' ?>" href="<?= e(url('peminjaman', ['status' => 'diajukan'])) ?>"><span>Menunggu persetujuan</span><strong><?= (int)($cnt['diajukan'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('peminjaman', ['status' => 'dipinjam'])) ?>"><span>Sedang dipinjam</span><strong><?= (int)($cnt['dipinjam'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('peminjaman', ['status' => 'terlambat'])) ?>"><span>Terlambat</span><strong><?= (int)($cnt['terlambat'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('peminjaman', ['status' => 'hilang'])) ?>"><span>Hilang</span><strong><?= (int)($cnt['hilang'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('pengembalian', ['tab' => 'riwayat', 'dari' => date('Y-m-d'), 'sampai' => date('Y-m-d')])) ?>"><span>Pengembalian hari ini</span><strong><?= (int)$kembaliHariIni ?></strong></a>
  <a class="stat" href="<?= e(url('pengembalian', ['tab' => 'denda', 'status' => 'belum_diselesaikan'])) ?>"><span>Denda belum selesai (<?= (int)$denda['jumlah'] ?>)</span><strong class="stat-money"><?= e(rupiah($denda['total'])) ?></strong></a>
</div>
<p class="muted small">Alat: <?= (int)$alat['tersedia'] ?> tersedia &middot; <?= (int)$alat['dipinjam'] ?> dipinjam &middot; <?= (int)$alat['rusak'] ?> rusak &middot; <?= (int)$alat['hilang'] ?> hilang</p>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head"><h2>Perlu persetujuan</h2><a href="<?= e(url('peminjaman', ['status' => 'diajukan'])) ?>">Lihat semua</a></div>
    <?php partial('peminjaman/_mini', ['rows' => $pending, 'aksi' => true, 'empty' => 'Tidak ada pengajuan yang menunggu.']); ?>
  </section>
  <section class="panel">
    <div class="panel-head"><h2>Alat sedang di luar</h2><a href="<?= e(url('pengembalian')) ?>">Pantau pengembalian</a></div>
    <?php partial('peminjaman/_mini', ['rows' => $aktif, 'aksi' => false, 'empty' => 'Tidak ada alat yang sedang dipinjam.']); ?>
  </section>
</div>

<section class="panel">
  <div class="panel-head"><h2>Aktivitas terbaru</h2><a href="<?= e(url('log')) ?>">Log lengkap</a></div>
  <?php if (!$logs): ?>
    <div class="empty"><p>Belum ada aktivitas.</p></div>
  <?php else: ?>
  <ul class="timeline">
    <?php foreach ($logs as $l): ?>
      <li><span class="tl-time"><?= e(tgl($l['created_at'], true)) ?></span><span><strong><?= e($l['nama_lengkap'] ?? 'Sistem') ?></strong> &middot; <?= e($l['aktivitas']) ?></span></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
