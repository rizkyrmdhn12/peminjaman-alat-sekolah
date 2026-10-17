<div class="page-head"><div><h1><?= e($title) ?></h1></div><div class="actions"><a class="btn btn-ghost" href="<?= e(url('pengembalian-detail', ['id' => $r['id_pengembalian']])) ?>">Kembali</a></div></div>
<form method="post" action="<?= e(url('pengembalian-update')) ?>" class="panel form narrow" data-loading>
  <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id_pengembalian'] ?>">
  <div class="field"><label for="catatan">Catatan</label><textarea id="catatan" name="catatan" rows="4" maxlength="500"><?= e($r['catatan'] ?? '') ?></textarea></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><span class="btn-label">Simpan</span></button><a class="btn btn-ghost" href="<?= e(url('pengembalian-detail', ['id' => $r['id_pengembalian']])) ?>">Batal</a></div>
</form>
