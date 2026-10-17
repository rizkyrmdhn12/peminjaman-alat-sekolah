<div class="page-head">
  <div><h1>Kategori Alat</h1><p class="muted">Pengelompokan alat inventaris.</p></div>
  <div class="actions"><a class="btn btn-primary" href="<?= e(url('kategori-create')) ?>">Tambah Kategori</a></div>
</div>
<form method="get" action="index.php" class="toolbar" data-loading-table>
  <input type="hidden" name="route" value="kategori">
  <div class="field grow"><label class="sr-only" for="q">Cari</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau deskripsi kategori"></div>
  <button class="btn btn-secondary" type="submit">Cari</button>
  <a class="btn btn-ghost" href="<?= e(url('kategori')) ?>">Reset</a>
</form>
<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Tidak ada kategori</strong><p>Coba ubah kata kunci pencarian.</p></div>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Nama kategori</th><th>Deskripsi</th><th data-sort>Jumlah alat</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $k): ?>
      <tr>
        <td data-label="Kategori"><strong><?= e($k['nama_kategori']) ?></strong></td>
        <td data-label="Deskripsi"><?= e($k['deskripsi'] ?? '-') ?></td>
        <td data-label="Jumlah alat" data-value="<?= (int)$k['jumlah_alat'] ?>"><?= (int)$k['jumlah_alat'] ?></td>
        <td data-label="Aksi" class="col-actions">
          <a class="btn btn-outline btn-xs" href="<?= e(url('kategori-edit', ['id' => $k['id_kategori']])) ?>">Ubah</a>
          <form method="post" action="<?= e(url('kategori-delete')) ?>" class="inline" data-confirm="Hapus kategori <?= e($k['nama_kategori']) ?>?">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$k['id_kategori'] ?>">
            <button class="btn btn-danger-outline btn-xs" type="submit">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'kategori', ['q' => $q]) ?>
