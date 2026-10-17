<?php $isEdit = $row !== null; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1></div><div class="actions"><a class="btn btn-ghost" href="<?= e(url('alat')) ?>">Kembali</a></div></div>
<form method="post" action="<?= e(url($action)) ?>" class="panel form" data-validate data-loading novalidate>
  <?= Csrf::field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$row['id_alat'] ?>"><?php endif; ?>
  <?php if ($terkunci): ?><div class="notice">Alat sedang dipinjam. Status dan kondisi hanya berubah melalui proses pengembalian.</div><?php endif; ?>
  <div class="form-grid">
    <div class="field"><label for="kode_inventaris">Kode inventaris</label><input id="kode_inventaris" name="kode_inventaris" maxlength="30" required value="<?= e(fv('kode_inventaris', $row)) ?>"><?= ferr('kode_inventaris') ?></div>
    <div class="field"><label for="nama_alat">Nama alat</label><input id="nama_alat" name="nama_alat" maxlength="100" required value="<?= e(fv('nama_alat', $row)) ?>"><?= ferr('nama_alat') ?></div>
    <div class="field">
      <label for="id_kategori">Kategori</label>
      <select id="id_kategori" name="id_kategori" required>
        <option value="">Pilih kategori</option>
        <?php foreach ($kategori as $k): ?><option value="<?= (int)$k['id_kategori'] ?>"<?= sel(fv('id_kategori', $row), $k['id_kategori']) ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?>
      </select>
      <?= ferr('id_kategori') ?>
    </div>
    <div class="field"><label for="lokasi_penyimpanan">Lokasi penyimpanan</label><input id="lokasi_penyimpanan" name="lokasi_penyimpanan" maxlength="100" value="<?= e(fv('lokasi_penyimpanan', $row)) ?>"><?= ferr('lokasi_penyimpanan') ?></div>
    <div class="field"><label for="merek">Merek</label><input id="merek" name="merek" maxlength="50" value="<?= e(fv('merek', $row)) ?>"><?= ferr('merek') ?></div>
    <div class="field"><label for="tipe_model">Tipe / model</label><input id="tipe_model" name="tipe_model" maxlength="100" value="<?= e(fv('tipe_model', $row)) ?>"><?= ferr('tipe_model') ?></div>
    <div class="field">
      <label for="kondisi">Kondisi</label>
      <select id="kondisi" name="kondisi" <?= $terkunci ? 'disabled' : 'required' ?>>
        <?php foreach (['baik', 'rusak_ringan', 'rusak_berat'] as $v): ?><option value="<?= e($v) ?>"<?= sel(fv('kondisi', $row, 'baik'), $v) ?>><?= e(label($v)) ?></option><?php endforeach; ?>
      </select>
      <?= ferr('kondisi') ?>
    </div>
    <div class="field">
      <label for="status_alat">Status</label>
      <select id="status_alat" name="status_alat" <?= $terkunci ? 'disabled' : 'required' ?>>
        <?php if ($terkunci): ?><option>Dipinjam</option><?php else: foreach (['tersedia', 'rusak', 'hilang'] as $v): ?><option value="<?= e($v) ?>"<?= sel(fv('status_alat', $row, 'tersedia'), $v) ?>><?= e(label($v)) ?></option><?php endforeach; endif; ?>
      </select>
      <?= ferr('status_alat') ?>
    </div>
  </div>
  <div class="field"><label for="deskripsi">Deskripsi <span class="opt">(opsional)</span></label><textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000"><?= e(fv('deskripsi', $row)) ?></textarea><?= ferr('deskripsi') ?></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><span class="btn-label">Simpan</span></button><a class="btn btn-ghost" href="<?= e(url('alat')) ?>">Batal</a></div>
</form>
