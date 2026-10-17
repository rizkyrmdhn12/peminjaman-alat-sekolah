<div class="page-head"><div><h1>Log Aktivitas</h1><p class="muted">Jejak audit seluruh aksi penting pada sistem.</p></div></div>
<form method="get" action="index.php" class="toolbar toolbar-wrap" data-loading-table>
  <input type="hidden" name="route" value="log">
  <div class="field grow"><label class="sr-only" for="q">Cari</label><input id="q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Cari aktivitas atau pengguna"></div>
  <select name="modul" aria-label="Modul" data-autosubmit>
    <option value="">Semua modul</option>
    <?php foreach ($moduls as $m): ?><option value="<?= e($m) ?>"<?= sel($f['modul'], $m) ?>><?= e(ucfirst($m)) ?></option><?php endforeach; ?>
  </select>
  <select name="aksi" aria-label="Aksi" data-autosubmit>
    <option value="">Semua aksi</option>
    <?php foreach ($aksi as $a): ?><option value="<?= e($a) ?>"<?= sel($f['aksi'], $a) ?>><?= e($a) ?></option><?php endforeach; ?>
  </select>
  <input type="date" name="dari" value="<?= e($f['dari']) ?>" aria-label="Dari tanggal" data-autosubmit>
  <input type="date" name="sampai" value="<?= e($f['sampai']) ?>" aria-label="Sampai tanggal" data-autosubmit>
  <button class="btn btn-secondary" type="submit">Cari</button>
  <a class="btn btn-ghost" href="<?= e(url('log')) ?>">Reset</a>
</form>
<div class="table-wrap" data-table-wrap>
<?php if (!$pg['rows']): ?>
  <div class="empty"><strong>Tidak ada log</strong><p>Coba ubah kata kunci atau filter.</p></div>
<?php else: ?>
  <table class="table responsive" data-sortable>
    <thead><tr><th data-sort>Waktu</th><th data-sort>Pengguna</th><th>Aktivitas</th><th data-sort>Modul</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($pg['rows'] as $l): ?>
      <tr>
        <td data-label="Waktu" data-value="<?= e($l['created_at']) ?>"><?= e(tgl($l['created_at'], true)) ?></td>
        <td data-label="Pengguna"><?= e($l['nama_lengkap'] ?? 'Sistem') ?><?= $l['username'] ? '<small class="sub">' . e($l['username']) . '</small>' : '' ?></td>
        <td data-label="Aktivitas"><?= e($l['aktivitas']) ?></td>
        <td data-label="Modul"><span class="badge badge-info"><?= e($l['modul']) ?></span></td>
        <td data-label="IP"><?= e($l['ip_address'] ?? '-') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?= pagination($pg, 'log', $f) ?>
