<?php $isEdit = $row !== null; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1></div><div class="actions"><a class="btn btn-ghost" href="<?= e(url('kategori')) ?>">Kembali</a></div></div>
<form method="post" action="<?= e(url($action)) ?>" class="panel form narrow" data-validate data-loading novalidate>
  <?= Csrf::field() ?>
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$row['id_kategori'] ?>"><?php endif; ?>
  <div class="field"><label for="nama_kategori">Nama kategori</label><input id="nama_kategori" name="nama_kategori" maxlength="50" required value="<?= e(fv('nama_kategori', $row)) ?>"><?= ferr('nama_kategori') ?></div>
  <div class="field"><label for="deskripsi">Deskripsi <span class="opt">(opsional)</span></label><textarea id="deskripsi" name="deskripsi" rows="3" maxlength="255"><?= e(fv('deskripsi', $row)) ?></textarea><?= ferr('deskripsi') ?></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><span class="btn-label">Simpan</span></button><a class="btn btn-ghost" href="<?= e(url('kategori')) ?>">Batal</a></div>
</form>
