<?php
class KategoriController extends Controller
{
    private function validate(array $in, ?int $except): array
    {
        $err = Validator::run($in, ['nama_kategori' => 'required|min:2|max:50', 'deskripsi' => 'max:255'], ['nama_kategori' => 'Nama kategori']);
        if (!isset($err['nama_kategori']) && (new KategoriModel())->nameTaken($in['nama_kategori'], $except)) {
            $err['nama_kategori'] = 'Nama kategori sudah digunakan.';
        }
        return $err;
    }

    public function index(): void
    {
        $q = mb_substr($this->get('q'), 0, 50);
        $this->render('kategori/index', ['title' => 'Kategori Alat', 'q' => $q, 'pg' => (new KategoriModel())->paginate($q, $this->page())]);
    }

    public function create(): void
    {
        $this->render('kategori/form', ['title' => 'Tambah Kategori', 'row' => null, 'action' => 'kategori-store']);
    }

    public function store(): void
    {
        $in = $this->only(['nama_kategori', 'deskripsi']);
        $err = $this->validate($in, null);
        if ($err) {
            Flash::withInput($in, $err);
            redirect('kategori-create');
        }
        try {
            $id = (new KategoriModel())->create($in['nama_kategori'], nz($in['deskripsi']));
            $this->log('CREATE', 'kategori', $id, 'menambah kategori ' . $in['nama_kategori']);
            Flash::set('success', 'Data berhasil disimpan.');
            redirect('kategori');
        } catch (Throwable $e) {
            $this->fail($e, 'kategori-create');
        }
    }

    public function edit(): void
    {
        $row = (new KategoriModel())->find($this->id());
        if (!$row) {
            abort(404, 'Kategori tidak ditemukan.');
        }
        $this->render('kategori/form', ['title' => 'Ubah Kategori', 'row' => $row, 'action' => 'kategori-update']);
    }

    public function update(): void
    {
        $id = $this->id();
        $km = new KategoriModel();
        if (!$km->find($id)) {
            abort(404, 'Kategori tidak ditemukan.');
        }
        $in = $this->only(['nama_kategori', 'deskripsi']);
        $err = $this->validate($in, $id);
        if ($err) {
            Flash::withInput($in, $err);
            redirect('kategori-edit', ['id' => $id]);
        }
        try {
            $km->update($id, $in['nama_kategori'], nz($in['deskripsi']));
            $this->log('UPDATE', 'kategori', $id, 'mengubah kategori ' . $in['nama_kategori']);
            Flash::set('success', 'Data berhasil disimpan.');
            redirect('kategori');
        } catch (Throwable $e) {
            $this->fail($e, 'kategori-edit', ['id' => $id]);
        }
    }

    public function delete(): void
    {
        $id = $this->id();
        try {
            $km = new KategoriModel();
            $row = $km->find($id);
            if (!$row) {
                throw new BusinessException('Kategori tidak ditemukan.');
            }
            if ($km->usedCount($id) > 0) {
                throw new BusinessException('Kategori tidak dapat dihapus karena masih digunakan oleh data alat.');
            }
            $km->delete($id);
            $this->log('DELETE', 'kategori', $id, 'menghapus kategori ' . $row['nama_kategori']);
            Flash::set('success', 'Data berhasil dihapus.');
        } catch (Throwable $e) {
            $this->fail($e, 'kategori');
        }
        redirect('kategori');
    }
}
