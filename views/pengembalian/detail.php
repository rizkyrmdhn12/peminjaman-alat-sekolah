<div class="page-head">
  <div><h1>Pengembalian &middot; <?= e($r['kode_peminjaman']) ?></h1><p class="muted">Dikembalikan <?= e(tgl($r['tanggal_pengembalian'], true)) ?> oleh <?= e($r['nama_petugas'] ?? '-') ?></p></div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(url('pengembalian', ['tab' => 'riwayat'])) ?>">Kembali</a>
    <?php if (Auth::can('pengembalian.update')): ?><a class="btn btn-outline" href="<?= e(url('pengembalian-edit', ['id' => $r['id_pengembalian']])) ?>">Ubah catatan</a><?php endif; ?>
  </div>
</div>
<section class="panel">
  <dl class="detail-list">
    <dt>Peminjam</dt><dd><?= e($r['nama_peminjam']) ?></dd>
    <dt>Batas kembali</dt><dd><?= e(tgl($r['batas_kembali'], true)) ?></dd>
    <dt>Total denda</dt><dd><strong><?= e(rupiah($r['total_denda'])) ?></strong></dd>
    <dt>Catatan</dt><dd><?= e($r['catatan'] ?? '-') ?></dd>
  </dl>
</section>
<section class="panel">
  <h2>Rincian alat</h2>
  <div class="table-wrap"><table class="table responsive">
    <thead><tr><th>Alat</th><th>Kondisi</th><th>JP terlambat</th><th>Denda terlambat</th><th>Denda rusak</th><th>Denda hilang</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
      <tr>
        <td data-label="Alat"><?= e($it['kode_inventaris']) ?> &middot; <?= e($it['nama_alat']) ?></td>
        <td data-label="Kondisi"><?= badge($it['kondisi']) ?></td>
        <td data-label="JP terlambat"><?= (int)$it['jumlah_jp_terlambat'] ?></td>
        <td data-label="Denda terlambat"><?= e(rupiah($it['denda_keterlambatan'])) ?></td>
        <td data-label="Denda rusak"><?= e(rupiah($it['denda_kerusakan'])) ?></td>
        <td data-label="Denda hilang"><?= e(rupiah($it['denda_kehilangan'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php if ($denda): ?>
<section class="panel">
  <h2>Status denda</h2>
  <div class="table-wrap"><table class="table responsive"><thead><tr><th>Jenis</th><th>Alat</th><th>Jumlah</th><th>Status</th><?php if (Auth::can('denda.update')): ?><th class="col-actions">Aksi</th><?php endif; ?></tr></thead><tbody>
  <?php foreach ($denda as $d): ?>
    <tr>
      <td data-label="Jenis"><?= e(label($d['jenis_denda'])) ?></td>
      <td data-label="Alat"><?= e($d['kode_inventaris']) ?> &middot; <?= e($d['nama_alat']) ?></td>
      <td data-label="Jumlah"><?= e(rupiah($d['jumlah_denda'])) ?></td>
      <td data-label="Status"><?= badge($d['status_denda']) ?></td>
      <?php if (Auth::can('denda.update')): ?>
      <td data-label="Aksi" class="col-actions">
        <?php if ($d['status_denda'] === 'belum_diselesaikan'): ?>
        <form method="post" action="<?= e(url('denda-selesai')) ?>" class="inline" data-confirm="Tandai selesai?"><?= Csrf::field() ?><input type="hidden" name="id_denda" value="<?= (int)$d['id_denda'] ?>"><button class="btn btn-outline btn-xs" type="submit">Tandai selesai</button></form>
        <?php endif; ?>
      </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endif; ?>
<?php if (Auth::can('pengembalian.delete') && (float)$r['total_denda'] <= 0): ?>
<section class="panel">
  <h2>Koreksi</h2>
  <p class="muted">Pengembalian tanpa denda dapat dibatalkan jika terjadi salah input. Transaksi akan kembali berstatus dipinjam.</p>
  <form method="post" action="<?= e(url('pengembalian-delete')) ?>" data-confirm="Batalkan pengembalian ini? Transaksi akan kembali berstatus dipinjam.">
    <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id_pengembalian'] ?>">
    <button class="btn btn-danger-outline" type="submit">Batalkan Pengembalian</button>
  </form>
</section>
<?php endif; ?>
