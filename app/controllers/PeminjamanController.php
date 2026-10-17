<?php
class PeminjamanController extends Controller
{
    private const STATUS = ['diajukan', 'disetujui', 'ditolak', 'dipinjam', 'terlambat', 'hilang', 'dikembalikan', 'dibatalkan'];

    private function filters(): array
    {
        return [
            'q' => mb_substr($this->get('q'), 0, 50),
            'status' => $this->enumGet('status', self::STATUS),
            'jenis' => $this->enumGet('jenis', ['online', 'offline']),
            'tanggal' => $this->dateGet('tanggal'),
        ];
    }

    /** Daftar transaksi (admin & petugas). */
    public function index(): void
    {
        $f = $this->filters();
        $this->render('peminjaman/index', ['title' => 'Peminjaman', 'f' => $f, 'status' => self::STATUS, 'pg' => (new PeminjamanModel())->paginate($f, $this->page())]);
    }

    /** Daftar transaksi milik peminjam yang sedang login (dipaksa lewat id_peminjam session). */
    public function saya(): void
    {
        $f = $this->filters();
        $f['q'] = '';
        $this->render('peminjaman/saya', ['title' => 'Peminjaman Saya', 'f' => $f, 'status' => self::STATUS, 'pg' => (new PeminjamanModel())->paginate($f, $this->page(), Auth::id())]);
    }

    public function detail(): void
    {
        $id = $this->id();
        $staff = Auth::can('peminjaman.view');
        // Peminjam: kepemilikan dicek di query (WHERE id_peminjam = user session) => anti-IDOR.
        $pm = new PeminjamanModel();
        $p = $pm->findFor($id, $staff ? null : Auth::id());
        if (!$p) {
            if ($staff) {
                abort(404, 'Data peminjaman tidak ditemukan.');
            }
            abort(403, 'Anda tidak memiliki akses ke data tersebut.');
        }
        $pg = new PengembalianModel();
        $this->render('peminjaman/detail', [
            'title' => $p['kode_peminjaman'],
            'p' => $p,
            'items' => $pm->details($id),
            'kembali' => $pg->findByPeminjaman($id),
            'denda' => $pg->dendaByPeminjaman($id),
            'staff' => $staff,
        ]);
    }

    private function formData(bool $offline): array
    {
        $jm = new JadwalModel();
        $jp = [];
        foreach ($jm->kelompokList() as $k) {
            $jp[(int)$k['id_kelompok_jadwal']] = JadwalService::opsiJp((int)$k['id_kelompok_jadwal']);
        }
        $data = [
            'offline' => $offline,
            'action' => $offline ? 'peminjaman-offline-store' : 'peminjaman-store',
            'hari' => JadwalService::hariIni(),
            'tanggal' => date('Y-m-d'),
            'kelompok' => $jm->kelompokList(),
            'jp' => $jp,
            'alat' => (new AlatModel())->tersedia(),
            'max' => MAX_ALAT_PER_TRANSAKSI,
            'peminjamList' => [],
            'kelompokUser' => null,
        ];
        if ($offline) {
            $data['peminjamList'] = (new UserModel())->peminjamAktif();
        } else {
            $u = (new UserModel())->find(Auth::id());
            $data['kelompokUser'] = $jm->kelompokByKelas($u && $u['id_kelas'] ? (int)$u['id_kelas'] : null);
            $data['user'] = $u;
        }
        return $data;
    }

    public function create(): void
    {
        $this->render('peminjaman/form', ['title' => 'Ajukan Peminjaman'] + $this->formData(false));
    }

    public function offline(): void
    {
        $this->render('peminjaman/form', ['title' => 'Peminjaman Offline'] + $this->formData(true));
    }

    public function store(): void
    {
        $this->simpan(false);
    }

    public function offlineStore(): void
    {
        $this->simpan(true);
    }

    private function simpan(bool $offline): void
    {
        $back = $offline ? 'peminjaman-offline' : 'peminjaman-create';
        try {
            $um = new UserModel();
            $jm = new JadwalModel();
            // Peminjam online SELALU dari session; offline dipilih petugas (divalidasi harus role peminjam aktif).
            $idPeminjam = $offline ? $this->id('id_peminjam') : Auth::id();
            $peminjam = $um->findPeminjamAktif($idPeminjam);
            if (!$peminjam) {
                throw new BusinessException($offline ? 'Peminjam tidak valid.' : 'Akun Anda tidak dapat mengajukan peminjaman.');
            }
            $idKelompok = $jm->kelompokByKelas($peminjam['id_kelas'] ? (int)$peminjam['id_kelas'] : null);
            if ($idKelompok === null) {
                $idKelompok = $this->id('id_kelompok_jadwal');
                if (!$jm->kelompokExists($idKelompok)) {
                    throw new BusinessException('Pilih kelompok jadwal.');
                }
            }
            $w = JadwalService::resolve($this->id('id_jadwal_mulai'), $this->id('id_jadwal_kembali'), $idKelompok);

            $ids = [];
            $raw = $_POST['alat'] ?? [];
            foreach (is_array($raw) ? $raw : [] as $v) {
                if (is_string($v) && ctype_digit($v)) {
                    $ids[] = (int)$v;
                }
            }
            $ids = array_values(array_unique($ids));
            if (!$ids) {
                throw new BusinessException('Pilih minimal satu alat.');
            }
            if (count($ids) > MAX_ALAT_PER_TRANSAKSI) {
                throw new BusinessException('Maksimal ' . MAX_ALAT_PER_TRANSAKSI . ' alat per transaksi.');
            }
            $res = (new PeminjamanModel())->buat([
                'id_peminjam' => $idPeminjam,
                'id_petugas' => $offline ? Auth::id() : null,
                'jenis' => $offline ? 'offline' : 'online',
                'status' => $offline ? 'dipinjam' : 'diajukan',
                'tanggal' => $w['tanggal'],
                'id_jadwal_mulai' => $this->id('id_jadwal_mulai'),
                'id_jadwal_kembali' => $this->id('id_jadwal_kembali'),
                'waktu_mulai' => $w['waktu_mulai'],
                'batas_kembali' => $w['batas_kembali'],
                'catatan' => nz(mb_substr($this->post('catatan'), 0, 500)),
            ], $ids);
            $this->log('BORROW', 'peminjaman', $res['id'], ($offline ? 'peminjaman offline ' : 'pengajuan ') . $res['kode'] . ' (' . count($ids) . ' alat)');
            Flash::set('success', $offline ? 'Peminjaman offline berhasil dicatat.' : 'Pengajuan berhasil dikirim.');
            redirect('peminjaman-detail', ['id' => $res['id']]);
        } catch (Throwable $e) {
            Flash::withInput($_POST);
            $this->fail($e, $back);
        }
    }

    private function next(int $id): void
    {
        if ($this->post('next') === 'list') {
            redirect('peminjaman');
        }
        redirect('peminjaman-detail', ['id' => $id]);
    }

    public function approve(): void
    {
        $id = $this->id();
        try {
            SyncService::run(true);
            (new PeminjamanModel())->approve($id, Auth::id());
            $this->log('APPROVE', 'peminjaman', $id, 'menyetujui peminjaman');
            Flash::set('success', 'Peminjaman berhasil disetujui.');
        } catch (Throwable $e) {
            $this->fail($e, 'peminjaman-detail', ['id' => $id]);
        }
        $this->next($id);
    }

    public function reject(): void
    {
        $id = $this->id();
        try {
            (new PeminjamanModel())->reject($id, Auth::id(), mb_substr($this->post('alasan'), 0, 200));
            $this->log('REJECT', 'peminjaman', $id, 'menolak peminjaman');
            Flash::set('success', 'Pengajuan berhasil ditolak.');
        } catch (Throwable $e) {
            $this->fail($e, 'peminjaman-detail', ['id' => $id]);
        }
        $this->next($id);
    }

    public function cancelOwn(): void
    {
        $id = $this->id();
        try {
            (new PeminjamanModel())->cancelOwn($id, Auth::id());   // WHERE id_peminjam = session user
            $this->log('UPDATE', 'peminjaman', $id, 'peminjam membatalkan pengajuan');
            Flash::set('success', 'Pengajuan berhasil dibatalkan.');
        } catch (Throwable $e) {
            $this->fail($e, 'peminjaman-saya');
        }
        redirect('peminjaman-saya');
    }

    public function edit(): void
    {
        $p = (new PeminjamanModel())->findFor($this->id(), null);
        if (!$p) {
            abort(404, 'Data peminjaman tidak ditemukan.');
        }
        $this->render('peminjaman/edit', ['title' => 'Ubah Catatan ' . $p['kode_peminjaman'], 'p' => $p]);
    }

    public function update(): void
    {
        $id = $this->id();
        $pm = new PeminjamanModel();
        if (!$pm->exists($id)) {
            abort(404, 'Data peminjaman tidak ditemukan.');
        }
        try {
            $pm->updateCatatan($id, nz(mb_substr($this->post('catatan'), 0, 500)));
            $this->log('UPDATE', 'peminjaman', $id, 'mengubah catatan peminjaman');
            Flash::set('success', 'Data berhasil disimpan.');
        } catch (Throwable $e) {
            $this->fail($e, 'peminjaman-edit', ['id' => $id]);
        }
        redirect('peminjaman-detail', ['id' => $id]);
    }

    public function cancel(): void
    {
        $id = $this->id();
        try {
            (new PeminjamanModel())->batalkan($id);
            $this->log('UPDATE', 'peminjaman', $id, 'admin membatalkan transaksi');
            Flash::set('success', 'Transaksi berhasil dibatalkan.');
        } catch (Throwable $e) {
            $this->fail($e, 'peminjaman-detail', ['id' => $id]);
        }
        redirect('peminjaman-detail', ['id' => $id]);
    }

    public function delete(): void
    {
        $id = $this->id();
        try {
            (new PeminjamanModel())->delete($id);
            $this->log('DELETE', 'peminjaman', $id, 'menghapus transaksi');
            Flash::set('success', 'Data berhasil dihapus.');
        } catch (Throwable $e) {
            $this->fail($e, 'peminjaman-detail', ['id' => $id]);
        }
        redirect('peminjaman');
    }
}
