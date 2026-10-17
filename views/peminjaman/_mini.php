<?php /* Tabel ringkas transaksi. Variabel: $rows, $empty, $aksi (bool: tombol setujui/tolak) */ ?>
<?php if (!$rows): ?>
  <div class="empty"><p><?= e($empty ?? 'Belum ada data.') ?></p></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table responsive">
    <thead><tr><th>Kode</th><th>Peminjam</th><th>Alat</th><th>Batas kembali</th><th>Status</th><?php if (!empty($aksi)): ?><th class="col-actions">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td data-label="Kode"><a href="<?= e(url('peminjaman-detail', ['id' => $r['id_peminjaman']])) ?>"><?= e($r['kode_peminjaman']) ?></a></td>
        <td data-label="Peminjam"><?= e($r['nama_peminjam']) ?></td>
        <td data-label="Alat"><?= e($r['daftar_alat']) ?></td>
        <td data-label="Batas kembali"><?= e(tgl($r['batas_kembali'], true)) ?></td>
        <td data-label="Status"><?= badge($r['status_peminjaman']) ?></td>
        <?php if (!empty($aksi)): ?>
        <td data-label="Aksi" class="col-actions">
          <form method="post" action="<?= e(url('peminjaman-approve')) ?>" class="inline">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id_peminjaman'] ?>"><input type="hidden" name="next" value="list">
            <button class="btn btn-primary btn-xs" type="submit">Setujui</button>
          </form>
          <form method="post" action="<?= e(url('peminjaman-reject')) ?>" class="inline" data-confirm="Tolak pengajuan ini?">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id_peminjaman'] ?>"><input type="hidden" name="next" value="list">
            <button class="btn btn-outline btn-xs" type="submit">Tolak</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
