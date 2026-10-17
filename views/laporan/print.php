<?php $jenisLabel = ['peminjaman' => 'Peminjaman', 'pengembalian' => 'Pengembalian', 'denda' => 'Denda']; ?>
<div class="print-sheet">
  <div class="print-head">
    <h1><?= e(APP_NAME) ?></h1>
    <p><?= e(APP_SCHOOL) ?></p>
    <h2>Laporan <?= e($jenisLabel[$f['jenis']] ?? ucfirst($f['jenis'])) ?></h2>
    <p class="print-meta">
      Periode: <?= e($f['dari'] !== '' ? tgl($f['dari']) : 'Awal') ?> s/d <?= e($f['sampai'] !== '' ? tgl($f['sampai']) : 'Sekarang') ?>
      &middot; Dicetak oleh <?= e($dicetakOleh) ?> pada <?= e(tgl(date('Y-m-d'), true)) ?>
    </p>
  </div>
  <table class="table print-table">
    <?php if ($f['jenis'] === 'peminjaman'): ?>
      <thead><tr><th>Kode</th><th>Peminjam</th><th>Alat</th><th>Waktu mulai</th><th>Batas kembali</th><th>Jenis</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($pg['rows'] as $r): ?><tr><td><?= e($r['kode_peminjaman']) ?></td><td><?= e($r['nama_peminjam']) ?></td><td><?= e($r['daftar_alat']) ?></td><td><?= e(tgl($r['waktu_mulai'], true)) ?></td><td><?= e(tgl($r['batas_kembali'], true)) ?></td><td><?= e(label($r['jenis_peminjaman'])) ?></td><td><?= e(label($r['status_peminjaman'])) ?></td></tr><?php endforeach; ?></tbody>
    <?php elseif ($f['jenis'] === 'pengembalian'): ?>
      <thead><tr><th>Kode</th><th>Peminjam</th><th>Alat</th><th>Dikembalikan</th><th>Jenis</th><th>Total denda</th><th>Petugas</th></tr></thead>
      <tbody><?php foreach ($pg['rows'] as $r): ?><tr><td><?= e($r['kode_peminjaman']) ?></td><td><?= e($r['nama_peminjam']) ?></td><td><?= e($r['daftar_alat']) ?></td><td><?= e(tgl($r['tanggal_pengembalian'], true)) ?></td><td><?= e(label($r['jenis_peminjaman'])) ?></td><td><?= e(rupiah($r['total_denda'])) ?></td><td><?= e($r['nama_petugas'] ?? '-') ?></td></tr><?php endforeach; ?></tbody>
    <?php else: ?>
      <thead><tr><th>Tanggal</th><th>Jenis</th><th>Alat</th><th>Peminjam</th><th>Jumlah</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($pg['rows'] as $r): ?><tr><td><?= e(tgl($r['tanggal_denda'], true)) ?></td><td><?= e(label($r['jenis_denda'])) ?></td><td><?= e($r['kode_inventaris']) ?> - <?= e($r['nama_alat']) ?></td><td><?= e($r['nama_peminjam']) ?></td><td><?= e(rupiah($r['jumlah_denda'])) ?></td><td><?= e(label($r['status_denda'])) ?></td></tr><?php endforeach; ?></tbody>
    <?php endif; ?>
  </table>
  <?php if ($pg['total_denda'] !== null): ?><p class="print-total">Total: <?= e(rupiah($pg['total_denda'])) ?> dari <?= (int)$pg['total'] ?> data.</p><?php endif; ?>
  <div class="print-actions"><button type="button" onclick="window.print()">Cetak halaman ini</button></div>
</div>
