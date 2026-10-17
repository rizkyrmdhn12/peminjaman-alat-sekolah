<div class="page-head">
  <div><h1><?= e($row['nama_alat']) ?></h1><p class="muted"><?= e($row['kode_inventaris']) ?> &middot; <?= e($row['nama_kategori']) ?></p></div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(url('alat')) ?>">Kembali</a>
    <?php if (Auth::can('alat.update')): ?><a class="btn btn-outline" href="<?= e(url('alat-edit', ['id' => $row['id_alat']])) ?>">Ubah</a><?php endif; ?>
    <?php if (Auth::can('peminjaman.create') && $row['status_alat'] === 'tersedia'): ?><a class="btn btn-primary" href="<?= e(url('peminjaman-create')) ?>">Ajukan Peminjaman</a><?php endif; ?>
  </div>
</div>
<div class="grid-2">
  <section class="panel">
    <h2>Informasi alat</h2>
    <dl class="detail-list">
      <dt>Kode inventaris</dt><dd><?= e($row['kode_inventaris']) ?></dd>
      <dt>Nama</dt><dd><?= e($row['nama_alat']) ?></dd>
      <dt>Kategori</dt><dd><?= e($row['nama_kategori']) ?></dd>
      <dt>Merek</dt><dd><?= e($row['merek'] ?? '-') ?></dd>
      <dt>Tipe / model</dt><dd><?= e($row['tipe_model'] ?? '-') ?></dd>
      <dt>Kondisi</dt><dd><?= badge($row['kondisi']) ?></dd>
      <dt>Status</dt><dd><?= badge($row['status_alat']) ?></dd>
      <dt>Lokasi</dt><dd><?= e($row['lokasi_penyimpanan'] ?? '-') ?></dd>
      <dt>Deskripsi</dt><dd><?= e($row['deskripsi'] ?? '-') ?></dd>
    </dl>
  </section>
  <?php if ($staff): ?>
  <section class="panel">
    <h2>Peminjaman</h2>
    <?php if ($aktif): ?>
      <p>Saat ini pada transaksi <a href="<?= e(url('peminjaman-detail', ['id' => $aktif['id_peminjaman']])) ?>"><?= e($aktif['kode_peminjaman']) ?></a> (<?= e($aktif['nama_peminjam']) ?>), batas kembali <?= e(tgl($aktif['batas_kembali'], true)) ?> <?= badge($aktif['status_peminjaman']) ?></p>
    <?php else: ?><p class="muted">Tidak sedang dipinjam.</p><?php endif; ?>
    <h3>Riwayat terakhir</h3>
    <?php if (!$riwayat): ?><p class="muted">Belum pernah dipinjam.</p><?php else: ?>
    <ul class="timeline">
      <?php foreach ($riwayat as $r): ?>
        <li><span class="tl-time"><?= e(tgl($r['tanggal_peminjaman'])) ?></span><span><a href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>"><?= e($r['kode_peminjaman']) ?></a> &middot; <?= e($r['nama_peminjam']) ?> <?= badge($r['status_peminjaman']) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>
