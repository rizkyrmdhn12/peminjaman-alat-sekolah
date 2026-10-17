<div class="page-head"><div><h1><?= e($title) ?></h1></div><div class="actions"><a class="btn btn-ghost" href="<?= e(url('peminjaman-detail', ['id' => $p['id_peminjaman']])) ?>">Kembali</a></div></div>
<form method="post" action="<?= e(url('peminjaman-update')) ?>" class="panel form narrow" data-loading>
  <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$p['id_peminjaman'] ?>">
  <p class="muted">Status, jadwal, dan alat tidak diubah di sini agar data transaksi tetap konsisten.</p>
  <div class="field"><label for="catatan">Catatan</label><textarea id="catatan" name="catatan" rows="4" maxlength="500"><?= e($p['catatan'] ?? '') ?></textarea></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit"><span class="btn-label">Simpan</span></button><a class="btn btn-ghost" href="<?= e(url('peminjaman-detail', ['id' => $p['id_peminjaman']])) ?>">Batal</a></div>
</form>
