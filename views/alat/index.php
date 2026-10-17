<?php $admin = Auth::can('alat.create'); $staff = Auth::can('peminjaman.view'); ?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p class="muted"><?= (int)$stat['tersedia'] ?> tersedia &middot; <?= (int)$stat['dipinjam'] ?> dipinjam &middot; <?= (int)$stat['rusak'] ?> rusak &middot; <?= (int)$stat['hilang'] ?> hilang</p></div>
  <div class="actions">
    <?php if ($admin): ?><a class="btn btn-primary" href="<?= e(url('alat-create')) ?>">Tambah Alat</a>
    <?php elseif (Auth::can('peminjaman.create')): ?><a class="btn btn-primary" href="<?= e(url('peminjaman-create')) ?>">Ajukan Peminjaman</a><?php endif; ?>
  </div>
</div>
<form method="get" action="index.php" class="toolbar" data-loading-table>
  <input type="hidden" name="route" value="alat">
  <div class="field grow"><label class="sr-only" for="q">Cari</label><input id="q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Cari kode, nama, merek, atau model"></div>
  <select name="id_kategori" aria-label="Kategori" data-autosubmit>
    <option value="">Semua kategori</option>
    <?php foreach ($kategori as $k): ?><option value="<?= (int)$k['id_kategori'] ?>"<?= sel($f['id_kategori'], $k['id_kategori']) ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?>
  </select>
  <select name="status_alat" aria-label="Status" data-autosubmit>
    <option value="">Semua status</option>
    <?php foreach (['tersedia', 'dipinjam', 'rusak', 'hilang'] as $s): ?><option value="<?= e($s) ?>"<?= sel($f['status_alat'], $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
  </select>
  <select name="kondisi" aria-label="Kondisi" data-autosubmit>
    <option value="">Semua kondisi</option>
    <?php foreach (['baik', 'rusak_ringan', 'rusak_berat'] as $s): ?><option value="<?= e($s) ?>"<?= sel($f['kondisi'], $s) ?>><?= e(label($s)) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-secondary" type="submit">Cari</button>
  <a class="btn btn-ghost" href="<?= e(url('alat')) ?>">Reset</a>
</form>
<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Alat tidak ditemukan</strong><p>Coba ubah kata kunci atau filter.</p></div>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Kode</th><th data-sort>Nama alat</th><th data-sort>Kategori</th><th>Merek / Model</th><th data-sort>Kondisi</th><th data-sort>Status</th><th>Lokasi</th><th class="col-actions">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $a): ?>
      <tr>
        <td data-label="Kode"><a href="<?= e(url('alat-show', ['id' => $a['id_alat']])) ?>"><?= e($a['kode_inventaris']) ?></a></td>
        <td data-label="Nama alat"><strong><?= e($a['nama_alat']) ?></strong></td>
        <td data-label="Kategori"><?= e($a['nama_kategori']) ?></td>
        <td data-label="Merek / Model"><?= e(trim(($a['merek'] ?? '') . ' ' . ($a['tipe_model'] ?? '')) ?: '-') ?></td>
        <td data-label="Kondisi"><?= badge($a['kondisi']) ?></td>
        <td data-label="Status"><?= badge($a['status_alat']) ?></td>
        <td data-label="Lokasi"><?= e($a['lokasi_penyimpanan'] ?? '-') ?></td>
        <td data-label="Aksi" class="col-actions">
          <a class="btn btn-outline btn-xs" href="<?= e(url('alat-show', ['id' => $a['id_alat']])) ?>">Detail</a>
          <?php if ($admin): ?>
            <a class="btn btn-outline btn-xs" href="<?= e(url('alat-edit', ['id' => $a['id_alat']])) ?>">Ubah</a>
            <form method="post" action="<?= e(url('alat-delete')) ?>" class="inline" data-confirm="Hapus alat <?= e($a['kode_inventaris']) ?>?">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$a['id_alat'] ?>">
              <button class="btn btn-danger-outline btn-xs" type="submit">Hapus</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'alat', $f) ?>
