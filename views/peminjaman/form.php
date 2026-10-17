<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted">Tanggal mengikuti server: <strong><?= e($hari ?? 'akhir pekan') ?>, <?= e(tgl($tanggal)) ?></strong>. Jam terisi otomatis dari JP.</p>
  </div>
</div>
<?php if ($hari === null): ?>
  <div class="empty"><strong>Peminjaman tidak tersedia hari ini</strong><p>Peminjaman mengikuti jadwal KBM dan hanya dibuka pada hari Senin sampai Jumat.</p></div>
<?php elseif (!$alat): ?>
  <div class="empty"><strong>Belum ada alat yang tersedia</strong><p>Semua alat sedang dipinjam atau tidak dapat digunakan. Coba lagi nanti.</p></div>
<?php else:
  $oldAlat = Flash::old('alat', []);
  $oldAlat = is_array($oldAlat) ? array_map('strval', $oldAlat) : [];
  $byKat = [];
  foreach ($alat as $a) { $byKat[$a['nama_kategori']][] = $a; }
?>
<form method="post" action="<?= e(url($action)) ?>" id="borrow-form" data-validate data-loading data-max="<?= (int)$max ?>" data-offline="<?= $offline ? '1' : '0' ?>" novalidate>
  <?= Csrf::field() ?>
  <div class="borrow-grid">
    <div class="borrow-main">
      <section class="panel">
        <h2>1. Peminjam dan jadwal</h2>
        <?php if ($offline): ?>
          <div class="field">
            <label for="id_peminjam">Peminjam</label>
            <select id="id_peminjam" name="id_peminjam" required>
              <option value="">Pilih peminjam</option>
              <?php foreach ($peminjamList as $u): ?>
                <option value="<?= (int)$u['id_user'] ?>" data-kelompok="<?= e($u['id_kelompok_jadwal'] ?? '') ?>"<?= sel(Flash::old('id_peminjam'), $u['id_user']) ?>><?= e($u['nama_lengkap']) ?> (<?= e($u['nama_kelas'] ?? label($u['jenis_pengguna'])) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <p class="who"><strong><?= e($user['nama_lengkap'] ?? '') ?></strong><span><?= e($user['nama_kelas'] ?? label($user['jenis_pengguna'] ?? '')) ?></span></p>
        <?php endif; ?>
        <div class="field" id="field-kelompok" hidden>
          <label for="id_kelompok_jadwal">Kelompok jadwal</label>
          <select id="id_kelompok_jadwal" name="id_kelompok_jadwal">
            <?php foreach ($kelompok as $k): ?><option value="<?= (int)$k['id_kelompok_jadwal'] ?>"<?= sel(Flash::old('id_kelompok_jadwal', 2), $k['id_kelompok_jadwal']) ?>><?= e($k['keterangan'] ?? $k['nama_kelompok']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid">
          <div class="field"><label for="id_jadwal_mulai">JP mulai</label><select id="id_jadwal_mulai" name="id_jadwal_mulai" required></select></div>
          <div class="field"><label for="id_jadwal_kembali">JP kembali</label><select id="id_jadwal_kembali" name="id_jadwal_kembali" required></select></div>
        </div>
        <p class="hint">Alat harus sudah kembali paling lambat saat JP kembali berakhir.</p>
      </section>

      <section class="panel">
        <div class="panel-head"><h2>2. Pilih alat</h2><span class="muted small" id="alat-count">0 dipilih</span></div>
        <div class="field"><label class="sr-only" for="alat-filter">Cari alat</label><input id="alat-filter" type="search" placeholder="Cari nama atau kode alat" autocomplete="off"></div>
        <?php foreach ($byKat as $kat => $list): ?>
          <fieldset class="alat-group">
            <legend><?= e($kat) ?></legend>
            <div class="alat-list">
            <?php foreach ($list as $a): ?>
              <label class="alat-item" data-search="<?= e(strtolower($a['nama_alat'] . ' ' . $a['kode_inventaris'] . ' ' . ($a['merek'] ?? ''))) ?>">
                <input type="checkbox" name="alat[]" value="<?= (int)$a['id_alat'] ?>" data-nama="<?= e($a['nama_alat'] . ' (' . $a['kode_inventaris'] . ')') ?>"<?= in_array((string)$a['id_alat'], $oldAlat, true) ? ' checked' : '' ?>>
                <span><strong><?= e($a['nama_alat']) ?></strong><small><?= e($a['kode_inventaris']) ?><?= $a['merek'] ? ' &middot; ' . e($a['merek']) : '' ?></small></span>
              </label>
            <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endforeach; ?>
        <div class="empty" id="alat-empty" hidden><p>Tidak ada alat yang cocok.</p></div>
      </section>

      <section class="panel">
        <div class="field"><label for="catatan">Catatan <span class="opt">(opsional)</span></label><textarea id="catatan" name="catatan" rows="2" maxlength="500" placeholder="Keperluan peminjaman"><?= e(Flash::old('catatan', '')) ?></textarea></div>
      </section>
    </div>

    <aside class="borrow-summary panel" aria-live="polite">
      <h2>Ringkasan</h2>
      <dl class="summary">
        <dt>Peminjam</dt><dd id="sum-peminjam"><?= $offline ? '-' : e($user['nama_lengkap'] ?? '') ?></dd>
        <dt>Hari</dt><dd><?= e($hari) ?></dd>
        <dt>Tanggal</dt><dd><?= e(tgl($tanggal)) ?></dd>
        <dt>Waktu</dt><dd id="sum-waktu">-</dd>
        <dt>Alat</dt><dd><ul id="sum-alat"><li class="muted">Belum ada alat dipilih</li></ul></dd>
      </dl>
      <button type="submit" class="btn btn-primary btn-block" id="borrow-submit"><span class="btn-label"><?= $offline ? 'Catat Peminjaman' : 'Kirim Pengajuan' ?></span></button>
      <p class="hint">Tidak ada biaya sewa. Denda hanya berlaku untuk keterlambatan, kerusakan, atau kehilangan.</p>
    </aside>
  </div>
  <script type="application/json" id="jp-data"><?= json_encode([
      'jp' => $jp,
      'kelompokUser' => $kelompokUser,
      'offline' => (bool)$offline,
      'old' => ['mulai' => (string)Flash::old('id_jadwal_mulai', ''), 'kembali' => (string)Flash::old('id_jadwal_kembali', '')],
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</form>
<?php endif; ?>
