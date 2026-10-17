/* Sangkuriang Equipment - JS vanilla (tanpa framework). */
(function () {
  'use strict';

  /* ---------- Sidebar mobile (drawer) ---------- */
  var sidebar = document.getElementById('sidebar');
  var scrim = document.getElementById('scrim');
  var toggle = document.getElementById('menuToggle');
  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    scrim && scrim.classList.remove('show');
    toggle && toggle.setAttribute('aria-expanded', 'false');
  }
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      var open = sidebar.classList.toggle('open');
      scrim && scrim.classList.toggle('show', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    scrim && scrim.addEventListener('click', closeSidebar);
  }

  /* ---------- Site nav (public) toggle sederhana untuk layar sempit ---------- */
  var siteNav = document.getElementById('siteNav');
  if (siteNav && window.innerWidth <= 720) {
    siteNav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { siteNav.classList.remove('open'); });
    });
  }

  /* ---------- Toast: auto-hide + tombol tutup ---------- */
  document.querySelectorAll('.toast').forEach(function (t) {
    var hide = function () { t.style.display = 'none'; };
    var btn = t.querySelector('.toast-close');
    btn && btn.addEventListener('click', hide);
    setTimeout(hide, 6000);
  });

  /* ---------- Show/hide password ---------- */
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = btn.parentElement.querySelector('input');
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Sembunyikan' : 'Lihat';
    });
  });

  /* ---------- Konfirmasi sebelum submit (aksi berbahaya) ---------- */
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  /* ---------- Loading state pada submit (cegah double submit) ---------- */
  document.querySelectorAll('form[data-loading]').forEach(function (f) {
    f.addEventListener('submit', function () {
      if (f.dataset.invalid === '1') return;
      var btn = f.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        var label = btn.querySelector('.btn-label');
        if (label) label.textContent = 'Menyimpan...';
      }
    });
  });

  /* ---------- Autosubmit filter (select/date di toolbar) ---------- */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form && el.form.submit(); });
  });

  /* ---------- Loading indikator ringan saat form filter tabel disubmit ---------- */
  document.querySelectorAll('form[data-loading-table]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var wrap = document.querySelector('[data-table-wrap]');
      if (wrap) wrap.style.opacity = '.5';
    });
  });

  /* ---------- Sort tabel sederhana (klik header) ---------- */
  document.querySelectorAll('table[data-sortable]').forEach(function (table) {
    var tbody = table.tBodies[0];
    if (!tbody) return;
    table.querySelectorAll('th[data-sort]').forEach(function (th, idx) {
      var col = Array.prototype.indexOf.call(th.parentElement.children, th);
      var dir = 1;
      th.addEventListener('click', function () {
        var rows = Array.prototype.slice.call(tbody.rows);
        rows.sort(function (a, b) {
          var ca = a.cells[col], cb = b.cells[col];
          var va = (ca.dataset.value || ca.textContent).trim().toLowerCase();
          var vb = (cb.dataset.value || cb.textContent).trim().toLowerCase();
          var na = parseFloat(va), nb = parseFloat(vb);
          var cmp = (!isNaN(na) && !isNaN(nb) && String(na) === va.replace(/,/g, '') ) ? na - nb : va.localeCompare(vb, 'id');
          return cmp * dir;
        });
        dir *= -1;
        rows.forEach(function (r) { tbody.appendChild(r); });
      });
    });
  });

  /* ---------- Filter pencarian klien pada daftar alat (form peminjaman) ---------- */
  var alatFilter = document.getElementById('alat-filter');
  if (alatFilter) {
    var items = document.querySelectorAll('.alat-item');
    var emptyMsg = document.getElementById('alat-empty');
    alatFilter.addEventListener('input', function () {
      var q = alatFilter.value.trim().toLowerCase();
      var visible = 0;
      items.forEach(function (it) {
        var match = it.dataset.search.indexOf(q) !== -1;
        it.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      document.querySelectorAll('.alat-group').forEach(function (g) {
        var any = Array.prototype.some.call(g.querySelectorAll('.alat-item'), function (it) { return it.style.display !== 'none'; });
        g.style.display = any ? '' : 'none';
      });
      if (emptyMsg) emptyMsg.hidden = visible !== 0;
    });
  }

  /* ---------- Toggle field kelas jika jenis_pengguna = siswa (register & form user) ---------- */
  document.querySelectorAll('[data-user-form]').forEach(function (form) {
    var jenis = form.querySelector('#jenis_pengguna');
    var fieldKelas = form.querySelector('#field-kelas');
    var kelas = form.querySelector('#id_kelas');
    if (!jenis || !fieldKelas) return;
    function sync() {
      var siswa = jenis.value === 'siswa';
      fieldKelas.hidden = !siswa;
      if (kelas) kelas.required = siswa;
      if (!siswa && kelas) kelas.value = '';
    }
    jenis.addEventListener('change', sync);
    sync();
  });

  /* ---------- Form peminjaman: JP dinamis + ringkasan (tanpa date/jam manual) ---------- */
  var borrowForm = document.getElementById('borrow-form');
  if (borrowForm) {
    var dataEl = document.getElementById('jp-data');
    var data = dataEl ? JSON.parse(dataEl.textContent) : { jp: {}, kelompokUser: null, offline: false, old: {} };
    var selMulai = document.getElementById('id_jadwal_mulai');
    var selKembali = document.getElementById('id_jadwal_kembali');
    var fieldKelompok = document.getElementById('field-kelompok');
    var selKelompok = document.getElementById('id_kelompok_jadwal');
    var selPeminjam = document.getElementById('id_peminjam');
    var sumWaktu = document.getElementById('sum-waktu');
    var sumAlat = document.getElementById('sum-alat');
    var sumPeminjam = document.getElementById('sum-peminjam');
    var alatCount = document.getElementById('alat-count');
    var maxAlat = parseInt(borrowForm.dataset.max || '10', 10);

    function currentKelompok() {
      if (data.offline) {
        if (selPeminjam) {
          var opt = selPeminjam.options[selPeminjam.selectedIndex];
          var k = opt ? opt.getAttribute('data-kelompok') : '';
          if (k) return parseInt(k, 10);
        }
        return selKelompok ? parseInt(selKelompok.value, 10) : null;
      }
      return data.kelompokUser || (selKelompok ? parseInt(selKelompok.value, 10) : null);
    }

    function fillJp(select, list, keepValue) {
      var prev = keepValue || select.value;
      select.innerHTML = '';
      if (!list || !list.length) {
        var o = document.createElement('option');
        o.value = ''; o.textContent = 'Tidak ada JP tersisa hari ini';
        select.appendChild(o);
        select.disabled = true;
        return;
      }
      select.disabled = false;
      list.forEach(function (jp) {
        var o = document.createElement('option');
        o.value = jp.id;
        o.textContent = 'JP ' + jp.nomor + ' (' + jp.mulai + ' - ' + jp.selesai + ')';
        select.appendChild(o);
      });
      if (prev && list.some(function (jp) { return String(jp.id) === String(prev); })) {
        select.value = prev;
      }
    }

    function refreshJp() {
      var k = currentKelompok();
      var list = k ? (data.jp[k] || []) : [];
      if (fieldKelompok) fieldKelompok.hidden = !!data.kelompokUser || (data.offline && !!k && selPeminjam && selPeminjam.value !== '' && !!currentKelompokFromPeminjam());
      fillJp(selMulai, list, data.old.mulai);
      updateKembaliOptions();
      data.old.mulai = null; data.old.kembali = null;
      updateSummary();
    }

    function currentKelompokFromPeminjam() {
      if (!selPeminjam) return null;
      var opt = selPeminjam.options[selPeminjam.selectedIndex];
      return opt ? opt.getAttribute('data-kelompok') : null;
    }

    function updateKembaliOptions() {
      var k = currentKelompok();
      var list = k ? (data.jp[k] || []) : [];
      var minNomor = selMulai.value ? (function () {
        var found = list.find(function (jp) { return String(jp.id) === selMulai.value; });
        return found ? found.nomor : 0;
      })() : 0;
      var filtered = list.filter(function (jp) { return jp.nomor >= minNomor; });
      fillJp(selKembali, filtered, data.old.kembali || selKembali.value);
    }

    function updateSummary() {
      var k = currentKelompok();
      var list = k ? (data.jp[k] || []) : [];
      var a = list.find(function (jp) { return String(jp.id) === selMulai.value; });
      var b = list.find(function (jp) { return String(jp.id) === selKembali.value; });
      sumWaktu.textContent = (a && b) ? (a.mulai + ' - ' + b.selesai) : '-';
      if (sumPeminjam && data.offline) {
        var opt = selPeminjam.options[selPeminjam.selectedIndex];
        sumPeminjam.textContent = (opt && opt.value) ? opt.textContent : '-';
      }
      var boxes = borrowForm.querySelectorAll('input[name="alat[]"]:checked');
      alatCount.textContent = boxes.length + ' dipilih';
      if (!boxes.length) {
        sumAlat.innerHTML = '<li class="muted">Belum ada alat dipilih</li>';
      } else {
        sumAlat.innerHTML = '';
        boxes.forEach(function (b2) {
          var li = document.createElement('li');
          li.textContent = b2.getAttribute('data-nama');
          sumAlat.appendChild(li);
        });
      }
    }

    selMulai.addEventListener('change', function () { updateKembaliOptions(); updateSummary(); });
    selKembali.addEventListener('change', updateSummary);
    selKelompok && selKelompok.addEventListener('change', refreshJp);
    selPeminjam && selPeminjam.addEventListener('change', refreshJp);
    borrowForm.querySelectorAll('input[name="alat[]"]').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var checked = borrowForm.querySelectorAll('input[name="alat[]"]:checked');
        if (checked.length > maxAlat) {
          cb.checked = false;
          window.alert('Maksimal ' + maxAlat + ' alat per transaksi.');
        }
        updateSummary();
      });
    });

    refreshJp();

    borrowForm.addEventListener('submit', function (e) {
      var errors = [];
      if (!selMulai.value || !selKembali.value) errors.push('Pilih JP mulai dan JP kembali.');
      if (!borrowForm.querySelectorAll('input[name="alat[]"]:checked').length) errors.push('Pilih minimal satu alat.');
      if (data.offline && selPeminjam && !selPeminjam.value) errors.push('Pilih peminjam.');
      if (errors.length) {
        e.preventDefault();
        borrowForm.dataset.invalid = '1';
        window.alert(errors.join('\n'));
        setTimeout(function () { borrowForm.dataset.invalid = '0'; }, 0);
      }
    });
  }

  /* ---------- Validasi ringan sisi klien (pelengkap; validasi utama di server) ---------- */
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var invalid = form.querySelector(':invalid');
      if (invalid) {
        e.preventDefault();
        form.dataset.invalid = '1';
        invalid.reportValidity();
        setTimeout(function () { form.dataset.invalid = '0'; }, 0);
        return;
      }
      var pw = form.querySelector('#password');
      var pwc = form.querySelector('#password_confirm');
      if (pw && pwc && pw.value && pwc.value && pw.value !== pwc.value) {
        e.preventDefault();
        form.dataset.invalid = '1';
        window.alert('Konfirmasi password tidak sama.');
        setTimeout(function () { form.dataset.invalid = '0'; }, 0);
      }
    });
  });
})();
