<?php $st = $p['status_peminjaman']; $aktif = in_array($st, PeminjamanModel::AKTIF, true); ?>
<div class="page-head">
  <div><h1><?= e($p['kode_peminjaman']) ?> <?= badge($st) ?></h1><p class="muted">Dibuat <?= e(tgl($p['created_at'], true)) ?> &middot; <?= e(label($p['jenis_peminjaman'])) ?></p></div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e($staff ? url('peminjaman') : url('peminjaman-saya')) ?>">Kembali</a>
    <?php if ($aktif && Auth::can('pengembalian.create')): ?><a class="btn btn-primary" href="<?= e(url('pengembalian-create', ['id' => $p['id_peminjaman']])) ?>">Proses Pengembalian</a><?php endif; ?>
    <?php if ($st === 'diajukan' && Auth::can('peminjaman.approve')): ?>
      <form method="post" action="<?= e(url('peminjaman-approve')) ?>" class="inline"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$p['id_peminjaman'] ?>"><button class="btn btn-primary" type="submit">Setujui</button></form>
    <?php endif; ?>
    <?php if ($st === 'diajukan' && !$staff): ?>
      <form method="post" action="<?= e(url('peminjaman-cancel-own')) ?>" class="inline" data-confirm="Batalkan pengajuan ini?"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$p['id_peminjaman'] ?>"><button class="btn btn-danger-outline" type="submit">Batalkan Pengajuan</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
  <section class="panel">
    <h2>Informasi transaksi</h2>
    <dl class="detail-list">
      <dt>Peminjam</dt><dd><?= e($p['nama_peminjam']) ?><?= $staff ? ' (' . e($p['username']) . ')' : '' ?></dd>
      <dt>Jenis pengguna</dt><dd><?= e(label($p['jenis_pengguna'])) ?><?= $p['nama_kelas'] ? ' &middot; ' . e($p['nama_kelas']) : '' ?></dd>
      <dt>Hari / tanggal</dt><dd><?= e($p['hari']) ?>, <?= e(tgl($p['tanggal_peminjaman'])) ?></dd>
      <dt>Jadwal</dt><dd>JP <?= (int)$p['jp_mulai'] ?> s/d JP <?= (int)$p['jp_kembali'] ?> &middot; <?= e(substr($p['waktu_mulai'], 11, 5)) ?> - <?= e(substr($p['batas_kembali'], 11, 5)) ?></dd>
      <dt>Batas kembali</dt><dd><?= e(tgl($p['batas_kembali'], true)) ?></dd>
      <dt>Dikembalikan</dt><dd><?= e(tgl($p['waktu_dikembalikan'], true)) ?></dd>
      <dt>Petugas</dt><dd><?= e($p['nama_petugas'] ?? '-') ?></dd>
      <dt>Catatan</dt><dd><?= e($p['catatan'] ?? '-') ?></dd>
    </dl>
    <?php if (!$staff && in_array($st, ['dipinjam', 'terlambat'], true)): ?>
      <p class="hint">Alat dikembalikan langsung kepada petugas (offline) sebelum batas kembali.</p>
    <?php endif; ?>
    <?php if ($st === 'hilang'): ?>
      <div class="notice notice-danger">Alat tidak dikembalikan hingga akhir KBM sehingga tercatat hilang. Jika ditemukan, serahkan kepada petugas. Denda kehilangan tetap tercatat.</div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Alat (<?= count($items) ?>)</h2>
    <div class="table-wrap">
      <table class="table responsive">
        <thead><tr><th>Kode</th><th>Alat</th><th>Kondisi awal</th><th>Status alat</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td data-label="Kode"><?= e($it['kode_inventaris']) ?></td>
            <td data-label="Alat"><?= e($it['nama_alat']) ?><small class="sub"><?= e(trim(($it['merek'] ?? '') . ' ' . ($it['tipe_model'] ?? ''))) ?></small></td>
            <td data-label="Kondisi awal"><?= badge($it['kondisi_saat_dipinjam']) ?></td>
            <td data-label="Status alat"><?= badge($it['status_alat']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<?php if ($kembali || $denda): ?>
<section class="panel">
  <div class="panel-head"><h2>Pengembalian &amp; denda</h2>
    <?php if ($kembali && $staff): ?><a href="<?= e(url('pengembalian-detail', ['id' => $kembali['id_pengembalian']])) ?>">Lihat pengembalian</a><?php endif; ?>
  </div>
  <?php if ($kembali): ?><p>Dikembalikan <?= e(tgl($kembali['tanggal_pengembalian'], true)) ?>. Total denda: <strong><?= e(rupiah($kembali['total_denda'])) ?></strong></p><?php endif; ?>
  <?php if ($denda): ?>
  <div class="table-wrap">
    <table class="table responsive">
      <thead><tr><th>Jenis</th><th>Alat</th><th>Keterangan</th><th>Jumlah</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($denda as $d): ?>
        <tr>
          <td data-label="Jenis"><?= e(label($d['jenis_denda'])) ?></td>
          <td data-label="Alat"><?= e($d['kode_inventaris']) ?> &middot; <?= e($d['nama_alat']) ?></td>
          <td data-label="Keterangan"><?= e($d['keterangan'] ?? '-') ?></td>
          <td data-label="Jumlah"><?= e(rupiah($d['jumlah_denda'])) ?></td>
          <td data-label="Status"><?= badge($d['status_denda']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?><p class="muted">Tidak ada denda.</p><?php endif; ?>
</section>
<?php endif; ?>

<?php if ($st === 'diajukan' && Auth::can('peminjaman.reject')): ?>
<section class="panel">
  <h2>Tolak pengajuan</h2>
  <form method="post" action="<?= e(url('peminjaman-reject')) ?>" class="inline-form" data-confirm="Tolak pengajuan ini?">
    <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$p['id_peminjaman'] ?>">
    <div class="field grow"><label class="sr-only" for="alasan">Alasan</label><input id="alasan" name="alasan" maxlength="200" placeholder="Alasan penolakan (opsional)"></div>
    <button class="btn btn-danger-outline" type="submit">Tolak</button>
  </form>
</section>
<?php endif; ?>

<?php if (Auth::can('peminjaman.update')): ?>
<section class="panel">
  <h2>Tindakan admin</h2>
  <div class="actions">
    <a class="btn btn-outline" href="<?= e(url('peminjaman-edit', ['id' => $p['id_peminjaman']])) ?>">Ubah catatan</a>
    <?php if (in_array($st, ['diajukan', 'dipinjam'], true)): ?>
    <form method="post" action="<?= e(url('peminjaman-cancel')) ?>" class="inline" data-confirm="Batalkan transaksi ini? Alat akan dilepas kembali.">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$p['id_peminjaman'] ?>"><button class="btn btn-outline" type="submit">Batalkan transaksi</button>
    </form>
    <?php endif; ?>
    <?php if (in_array($st, ['diajukan', 'ditolak', 'dibatalkan'], true) && Auth::can('peminjaman.delete')): ?>
    <form method="post" action="<?= e(url('peminjaman-delete')) ?>" class="inline" data-confirm="Hapus transaksi ini secara permanen?">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$p['id_peminjaman'] ?>"><button class="btn btn-danger-outline" type="submit">Hapus</button>
    </form>
    <?php endif; ?>
  </div>
  <?php if (!in_array($st, ['diajukan', 'ditolak', 'dibatalkan'], true)): ?><p class="hint">Transaksi yang sudah berjalan disimpan sebagai histori dan tidak dapat dihapus.</p><?php endif; ?>
</section>
<?php endif; ?>
