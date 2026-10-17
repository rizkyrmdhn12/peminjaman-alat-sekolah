<?php
class AlatController extends Controller
{
    private const KONDISI = ['baik', 'rusak_ringan', 'rusak_berat'];
    private const STATUS = ['tersedia', 'dipinjam', 'rusak', 'hilang'];

    private function input(): array
    {
        return $this->only(['id_kategori', 'kode_inventaris', 'nama_alat', 'merek', 'tipe_model', 'deskripsi', 'kondisi', 'status_alat', 'lokasi_penyimpanan']);
    }

    private function validate(array $in, ?int $except): array
    {
        $err = Validator::run($in, [
            'id_kategori' => 'required|int',
            'kode_inventaris' => 'required|max:30|kode',
            'nama_alat' => 'required|max:100',
            'merek' => 'max:50',
            'tipe_model' => 'max:100',
            'deskripsi' => 'max:2000',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'status_alat' => 'required|in:tersedia,rusak,hilang',
            'lokasi_penyimpanan' => 'max:100',
        ], ['id_kategori' => 'Kategori', 'kode_inventaris' => 'Kode inventaris', 'nama_alat' => 'Nama alat', 'status_alat' => 'Status', 'lokasi_penyimpanan' => 'Lokasi']);
        if (!isset($err['id_kategori']) && !(new KategoriModel())->find((int)$in['id_kategori'])) {
            $err['id_kategori'] = 'Kategori tidak ditemukan.';
        }
        if (!isset($err['kode_inventaris']) && (new AlatModel())->kodeTaken($in['kode_inventaris'], $except)) {
            $err['kode_inventaris'] = 'Kode inventaris sudah digunakan.';
        }
        if (!isset($err['status_alat']) && !isset($err['kondisi']) && $in['status_alat'] === 'tersedia' && $in['kondisi'] !== 'baik') {
            $err['status_alat'] = 'Alat yang rusak tidak boleh berstatus tersedia.';
        }
        return $err;
    }

    private function payload(array $in): array
    {
        $in['merek'] = nz($in['merek']);
        $in['tipe_model'] = nz($in['tipe_model']);
        $in['deskripsi'] = nz($in['deskripsi']);
        $in['lokasi_penyimpanan'] = nz($in['lokasi_penyimpanan']);
        return $in;
    }

    public function index(): void
    {
        $f = [
            'q' => mb_substr($this->get('q'), 0, 50),
            'id_kategori' => ctype_digit($this->get('id_kategori')) ? $this->get('id_kategori') : '',
            'status_alat' => $this->enumGet('status_alat', self::STATUS),
            'kondisi' => $this->enumGet('kondisi', self::KONDISI),
        ];
        $this->render('alat/index', [
            'title' => Auth::can('alat.create') ? 'Data Alat' : 'Daftar Alat',
            'f' => $f,
            'pg' => (new AlatModel())->paginate($f, $this->page()),
            'kategori' => (new KategoriModel())->semua(),
            'stat' => (new AlatModel())->countByStatus(),
        ]);
    }

    public function show(): void
    {
        $am = new AlatModel();
        $row = $am->find($this->id());
        if (!$row) {
            abort(404, 'Alat tidak ditemukan.');
        }
        $staff = Auth::can('peminjaman.view');
        $this->render('alat/show', [
            'title' => $row['nama_alat'],
            'row' => $row,
            'staff' => $staff,
            'aktif' => $staff ? $am->peminjamanAktif((int)$row['id_alat']) : null,
            'riwayat' => $staff ? $am->riwayat((int)$row['id_alat']) : [],
        ]);
    }

    public function create(): void
    {
        $this->render('alat/form', ['title' => 'Tambah Alat', 'row' => null, 'kategori' => (new KategoriModel())->semua(), 'action' => 'alat-store', 'terkunci' => false]);
    }

    public function store(): void
    {
        $in = $this->input();
        $err = $this->validate($in, null);
        if ($err) {
            Flash::withInput($in, $err);
            redirect('alat-create');
        }
        try {
            $id = (new AlatModel())->create($this->payload($in));
            $this->log('CREATE', 'alat', $id, 'menambah alat ' . $in['kode_inventaris']);
            Flash::set('success', 'Data berhasil disimpan.');
            redirect('alat');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Flash::withInput($in, ['kode_inventaris' => 'Kode inventaris sudah digunakan.']);
                redirect('alat-create');
            }
            $this->fail($e, 'alat-create');
        }
    }

    public function edit(): void
    {
        $row = (new AlatModel())->find($this->id());
        if (!$row) {
            abort(404, 'Alat tidak ditemukan.');
        }
        $this->render('alat/form', ['title' => 'Ubah Alat', 'row' => $row, 'kategori' => (new KategoriModel())->semua(), 'action' => 'alat-update', 'terkunci' => $row['status_alat'] === 'dipinjam']);
    }

    public function update(): void
    {
        $id = $this->id();
        $am = new AlatModel();
        $row = $am->find($id);
        if (!$row) {
            abort(404, 'Alat tidak ditemukan.');
        }
        $in = $this->input();
        $terkunci = $row['status_alat'] === 'dipinjam';
        if ($terkunci) {
            // Alat sedang dipinjam: status & kondisi hanya berubah lewat proses pengembalian.
            $in['status_alat'] = 'tersedia';
            $in['kondisi'] = 'baik';
        }
        $err = $this->validate($in, $id);
        if ($err) {
            Flash::withInput($in, $err);
            redirect('alat-edit', ['id' => $id]);
        }
        if ($terkunci) {
            $in['status_alat'] = 'dipinjam';
            $in['kondisi'] = $row['kondisi'];
        }
        try {
            $am->update($id, $this->payload($in));
            $this->log('UPDATE', 'alat', $id, 'mengubah alat ' . $in['kode_inventaris']);
            Flash::set('success', 'Data berhasil disimpan.');
            redirect('alat');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Flash::withInput($in, ['kode_inventaris' => 'Kode inventaris sudah digunakan.']);
                redirect('alat-edit', ['id' => $id]);
            }
            $this->fail($e, 'alat-edit', ['id' => $id]);
        }
    }

    public function delete(): void
    {
        $id = $this->id();
        try {
            $am = new AlatModel();
            $row = $am->find($id);
            if (!$row) {
                throw new BusinessException('Alat tidak ditemukan.');
            }
            if ($row['status_alat'] === 'dipinjam') {
                throw new BusinessException('Alat yang sedang dipinjam tidak dapat dihapus.');
            }
            if ($am->hasHistory($id)) {
                throw new BusinessException('Alat memiliki riwayat peminjaman sehingga tidak dapat dihapus. Ubah statusnya menjadi rusak jika tidak digunakan lagi.');
            }
            $am->delete($id);
            $this->log('DELETE', 'alat', $id, 'menghapus alat ' . $row['kode_inventaris']);
            Flash::set('success', 'Data berhasil dihapus.');
        } catch (Throwable $e) {
            $this->fail($e, 'alat');
        }
        redirect('alat');
    }
}
