<div class="page-head">
  <div><h1>Halo, <?= e(explode(' ', trim($auth['nama_lengkap']))[0]) ?></h1><p class="muted"><?= $hari ? e('Hari ini ' . $hari . '. Pengajuan hanya untuk hari berjalan.') : 'Hari ini bukan hari KBM. Pengajuan dibuka kembali pada hari Senin-Jumat.' ?></p></div>
  <div class="actions"><a class="btn btn-primary btn-lg" href="<?= e(url('peminjaman-create')) ?>">Ajukan Peminjaman</a></div>
</div>
<div class="stats stats-3">
  <a class="stat" href="<?= e(url('peminjaman-saya', ['status' => 'dipinjam'])) ?>"><span>Peminjaman aktif</span><strong><?= (int)(($cnt['dipinjam'] ?? 0) + ($cnt['terlambat'] ?? 0)) ?></strong></a>
  <a class="stat" href="<?= e(url('peminjaman-saya', ['status' => 'diajukan'])) ?>"><span>Menunggu persetujuan</span><strong><?= (int)($cnt['diajukan'] ?? 0) ?></strong></a>
  <a class="stat" href="<?= e(url('peminjaman-saya')) ?>"><span>Total transaksi</span><strong><?= (int)array_sum($cnt) ?></strong></a>
</div>
<div class="grid-2">
  <section class="panel">
    <div class="panel-head"><h2>Alat yang sedang saya pinjam</h2></div>
    <?php partial('peminjaman/_mini', ['rows' => $aktif, 'aksi' => false, 'empty' => 'Tidak ada alat yang sedang dipinjam.']); ?>
    <?php if ($aktif): ?><p class="hint">Alat dikembalikan langsung kepada petugas sebelum batas JP berakhir.</p><?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel-head"><h2>Status pengajuan</h2></div>
    <?php partial('peminjaman/_mini', ['rows' => $diajukan, 'aksi' => false, 'empty' => 'Tidak ada pengajuan yang menunggu.']); ?>
  </section>
</div>
<section class="panel">
  <div class="panel-head"><h2>Riwayat terakhir</h2><a href="<?= e(url('peminjaman-saya')) ?>">Semua riwayat</a></div>
  <?php partial('peminjaman/_mini', ['rows' => $riwayat, 'aksi' => false, 'empty' => 'Belum ada riwayat peminjaman.']); ?>
</section>
