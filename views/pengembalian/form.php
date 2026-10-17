<?php $sudahHilang = $p['status_peminjaman'] === 'hilang'; ?>
<div class="page-head">
  <div><h1>Proses Pengembalian &middot; <?= e($p['kode_peminjaman']) ?></h1><p class="muted">Peminjam: <strong><?= e($p['nama_peminjam']) ?></strong> &middot; Batas kembali: <?= e(tgl($p['batas_kembali'], true)) ?></p></div>
  <div class="actions"><a class="btn btn-ghost" href="<?= e(url('peminjaman-detail', ['id' => $p['id_peminjaman']])) ?>">Kembali</a></div>
</div>

<?php if ($sudahHilang): ?>
  <div class="notice notice-danger">Transaksi ini sudah berstatus <strong>hilang</strong> (tidak dikembalikan hingga akhir KBM). Denda kehilangan sudah tercatat. Gunakan formulir ini hanya jika alat <strong>ditemukan</strong> dan dikembalikan.</div>
<?php elseif ($jp > 0): ?>
  <div class="notice">Transaksi ini <strong>terlambat <?= (int)$jp ?> JP</strong> berdasarkan jadwal KBM. Estimasi denda keterlambatan: <?= e(rupiah($jp * DENDA_TERLAMBAT_PER_JP)) ?> per alat yang dikembalikan baik.</div>
<?php endif; ?>

<?php if ($dendaLama): ?>
<section class="panel">
  <h2>Denda yang sudah tercatat</h2>
  <div class="table-wrap"><table class="table responsive"><thead><tr><th>Jenis</th><th>Alat</th><th>Keterangan</th><th>Jumlah</th></tr></thead><tbody>
  <?php foreach ($dendaLama as $d): ?><tr><td data-label="Jenis"><?= e(label($d['jenis_denda'])) ?></td><td data-label="Alat"><?= e($d['kode_inventaris']) ?> &middot; <?= e($d['nama_alat']) ?></td><td data-label="Keterangan"><?= e($d['keterangan'] ?? '-') ?></td><td data-label="Jumlah"><?= e(rupiah($d['jumlah_denda'])) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endif; ?>

<form method="post" action="<?= e(url('pengembalian-store')) ?>" class="panel form" data-loading data-confirm="Simpan data pengembalian ini?">
  <?= Csrf::field() ?><input type="hidden" name="id_peminjaman" value="<?= (int)$p['id_peminjaman'] ?>">
  <div class="table-wrap">
    <table class="table responsive">
      <thead><tr><th>Alat</th><th>Kondisi saat dipinjam</th><th>Kondisi sekarang</th><th>Catatan</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): $d = (int)$it['id_detail_peminjaman']; ?>
        <tr>
          <td data-label="Alat"><strong><?= e($it['nama_alat']) ?></strong><small class="sub"><?= e($it['kode_inventaris']) ?></small></td>
          <td data-label="Kondisi awal"><?= badge($it['kondisi_saat_dipinjam']) ?></td>
          <td data-label="Kondisi sekarang">
            <select name="kondisi[<?= $d ?>]" required>
              <option value="baik">Baik</option>
              <option value="rusak_ringan">Rusak ringan (<?= e(rupiah(DENDA_RUSAK_RINGAN)) ?>)</option>
              <option value="rusak_berat">Rusak berat (<?= e(rupiah(DENDA_RUSAK_BERAT)) ?>)</option>
              <?php if (!$sudahHilang): ?><option value="hilang">Hilang (<?= e(rupiah(DENDA_HILANG)) ?>)</option><?php endif; ?>
            </select>
          </td>
          <td data-label="Catatan"><input type="text" name="catatan_item[<?= $d ?>]" maxlength="255" placeholder="Opsional"></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="field"><label for="catatan">Catatan pengembalian <span class="opt">(opsional)</span></label><textarea id="catatan" name="catatan" rows="2" maxlength="500"></textarea></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><span class="btn-label">Simpan Pengembalian</span></button><a class="btn btn-ghost" href="<?= e(url('peminjaman-detail', ['id' => $p['id_peminjaman']])) ?>">Batal</a></div>
</form>
