<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="eyebrow"><?= e(APP_SCHOOL) ?></p>
      <h1>Alat sekolah siap dipinjam. Prosesnya lebih teratur.</h1>
      <p class="lead">Ajukan peminjaman proyektor, kamera, dan perangkat multimedia lain berdasarkan jam pelajaran. Petugas menyetujui, sistem mencatat kondisi dan denda bila ada. Tanpa biaya sewa.</p>
      <div class="actions">
        <a class="btn btn-primary btn-lg" href="<?= e(url(Auth::check() ? Auth::homeRoute() : 'register')) ?>"><?= Auth::check() ? 'Buka Dashboard' : 'Mulai Meminjam' ?></a>
        <?php if (!Auth::check()): ?><a class="btn btn-outline btn-lg" href="<?= e(url('login')) ?>">Masuk</a><?php endif; ?>
      </div>
    </div>
    <div class="hero-visual" aria-hidden="true">
      <div class="mock">
        <div class="mock-bar"><span></span><span></span><span></span><em>Daftar Alat</em></div>
        <div class="mock-row"><div><strong>Projector</strong><small>PRJ-001 &middot; Ruang Presentasi</small></div><?= badge('tersedia') ?></div>
        <div class="mock-row"><div><strong>Camera</strong><small>CAM-001 &middot; Ruang Multimedia</small></div><?= badge('dipinjam') ?></div>
        <div class="mock-row"><div><strong>HDMI Cable</strong><small>HDM-001 &middot; Ruang Presentasi</small></div><?= badge('tersedia') ?></div>
        <div class="mock-row"><div><strong>Microphone</strong><small>MIC-001 &middot; Ruang Multimedia</small></div><?= badge('tersedia') ?></div>
        <div class="mock-slot">
          <span>Peminjaman hari ini</span>
          <strong>JP 1 &ndash; JP 3 &middot; 08.05 &ndash; 09.50</strong>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="fitur">
  <div class="container">
    <div class="section-head"><h2>Dirancang untuk alur peminjaman sekolah</h2><p>Setiap tahap tercatat, dari pengajuan sampai alat kembali ke ruang penyimpanan.</p></div>
    <div class="features">
      <div class="feature"><span class="feature-no">01</span><h3>Peminjaman Online</h3><p>Pilih alat dan JP dari akun sendiri. Tanggal dan jam mengikuti jadwal KBM, tanpa mengetik manual.</p></div>
      <div class="feature"><span class="feature-no">02</span><h3>Peminjaman Offline</h3><p>Petugas mencatat peminjaman langsung di tempat untuk peminjam yang datang ke ruang sarana.</p></div>
      <div class="feature"><span class="feature-no">03</span><h3>Persetujuan Petugas</h3><p>Setiap pengajuan online diperiksa petugas sebelum alat dinyatakan dipinjam.</p></div>
      <div class="feature"><span class="feature-no">04</span><h3>Pengembalian Tercatat</h3><p>Kondisi tiap alat dicek saat dikembalikan dan tersimpan sebagai riwayat.</p></div>
      <div class="feature"><span class="feature-no">05</span><h3>Denda Transparan</h3><p>Denda hanya untuk terlambat, rusak, atau hilang, dengan nominal yang jelas dan dihitung per JP.</p></div>
      <div class="feature"><span class="feature-no">06</span><h3>Inventaris Terorganisir</h3><p>Kode inventaris, kategori, kondisi, dan status setiap alat selalu terpantau.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" id="cara-kerja">
  <div class="container">
    <div class="section-head"><h2>Cara kerja</h2><p>Tujuh langkah dari memilih alat sampai pengembalian.</p></div>
    <ol class="steps">
      <li><strong>Pilih alat</strong><span>Lihat alat yang tersedia dan pilih satu atau beberapa.</span></li>
      <li><strong>Pilih JP</strong><span>Tentukan JP mulai dan JP kembali; jam terisi otomatis.</span></li>
      <li><strong>Ajukan</strong><span>Periksa ringkasan, lalu kirim pengajuan.</span></li>
      <li><strong>Tunggu persetujuan</strong><span>Petugas menyetujui atau menolak pengajuan.</span></li>
      <li><strong>Gunakan alat</strong><span>Ambil alat di ruang sarana dan gunakan selama JP.</span></li>
      <li><strong>Kembalikan</strong><span>Serahkan alat langsung kepada petugas sebelum batas JP.</span></li>
      <li><strong>Tercatat</strong><span>Sistem mencatat kondisi dan denda jika ada.</span></li>
    </ol>
  </div>
</section>

<section class="section" id="alat">
  <div class="container">
    <div class="section-head"><h2>Alat yang dapat dipinjam</h2><p>Dua kategori utama untuk kegiatan belajar dan presentasi.</p></div>
    <div class="cats">
      <div class="cat"><h3>Multimedia</h3><ul><li>Camera</li><li>Handycam</li><li>Tripod</li><li>Microphone</li><li>Headset</li><li>Speaker</li><li>Recorder</li></ul></div>
      <div class="cat"><h3>Presentasi</h3><ul><li>Projector</li><li>Projector Screen</li><li>Presenter Pointer</li><li>Kabel HDMI &amp; VGA</li><li>Adapter/Converter</li><li>Presentation Speaker</li></ul></div>
    </div>
    <div class="cta-band">
      <div><h2>Butuh alat untuk pelajaran berikutnya?</h2><p>Buat akun peminjam dalam satu menit.</p></div>
      <a class="btn btn-primary btn-lg" href="<?= e(url(Auth::check() ? Auth::homeRoute() : 'register')) ?>"><?= Auth::check() ? 'Buka Dashboard' : 'Daftar Sekarang' ?></a>
    </div>
  </div>
</section>
