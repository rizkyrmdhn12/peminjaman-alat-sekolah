<?php
class UserController extends Controller
{
    private const ROLES = ['admin', 'petugas', 'peminjam'];

    private function input(): array
    {
        $in = $this->only(['username', 'nama_lengkap', 'jenis_pengguna', 'id_kelas', 'nomor_identitas', 'no_hp', 'email', 'role', 'status']);
        $in['password'] = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $in['password_confirm'] = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
        return $in;
    }

    private function payload(array $in): array
    {
        return [
            'id_kelas' => $in['jenis_pengguna'] === 'siswa' ? (int)$in['id_kelas'] : null,
            'username' => $in['username'],
            'nama_lengkap' => $in['nama_lengkap'],
            'jenis_pengguna' => nz($in['jenis_pengguna']),
            'nomor_identitas' => nz($in['nomor_identitas']),
            'no_hp' => nz($in['no_hp']),
            'email' => nz($in['email']),
            'role' => $in['role'],
            'status' => $in['status'],
        ];
    }

    public function index(): void
    {
        $f = [
            'q' => mb_substr($this->get('q'), 0, 50),
            'role' => $this->enumGet('role', self::ROLES),
            'status' => $this->enumGet('status', ['aktif', 'nonaktif']),
        ];
        $this->render('users/index', ['title' => 'Pengguna', 'f' => $f, 'pg' => (new UserModel())->paginate($f, $this->page())]);
    }

    public function create(): void
    {
        $this->render('users/form', ['title' => 'Tambah Pengguna', 'row' => null, 'kelas' => (new KelasModel())->aktif(), 'action' => 'users-store', 'self' => false]);
    }

    public function store(): void
    {
        $in = $this->input();
        $um = new UserModel();
        $err = $um->validate($in, null, true, true);
        if ($err) {
            Flash::withInput($in, $err);
            redirect('users-create');
        }
        try {
            $id = $um->create($this->payload($in) + ['password' => password_hash($in['password'], PASSWORD_DEFAULT)]);
            $this->log('CREATE', 'users', $id, 'menambah pengguna ' . $in['username'] . ' (' . $in['role'] . ')');
            Flash::set('success', 'Data berhasil disimpan.');
            redirect('users');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Flash::withInput($in, ['username' => 'Username atau nomor identitas sudah terdaftar.']);
                redirect('users-create');
            }
            $this->fail($e, 'users-create');
        }
    }

    public function edit(): void
    {
        $row = (new UserModel())->find($this->id());
        if (!$row) {
            abort(404, 'Pengguna tidak ditemukan.');
        }
        $this->render('users/form', ['title' => 'Ubah Pengguna', 'row' => $row, 'kelas' => (new KelasModel())->aktif(), 'action' => 'users-update', 'self' => (int)$row['id_user'] === Auth::id()]);
    }

    public function update(): void
    {
        $id = $this->id();
        $um = new UserModel();
        $row = $um->find($id);
        if (!$row) {
            abort(404, 'Pengguna tidak ditemukan.');
        }
        $in = $this->input();
        $err = $um->validate($in, $id, false, true);
        if ($id === Auth::id() && ($in['role'] !== $row['role'] || $in['status'] !== 'aktif')) {
            $err['role'] = 'Anda tidak dapat mengubah role atau menonaktifkan akun sendiri.';
        }
        if ($err) {
            Flash::withInput($in, $err);
            redirect('users-edit', ['id' => $id]);
        }
        try {
            $um->update($id, $this->payload($in), $in['password'] !== '' ? password_hash($in['password'], PASSWORD_DEFAULT) : null);
            $this->log('UPDATE', 'users', $id, 'mengubah pengguna ' . $in['username']);
            Flash::set('success', 'Data berhasil disimpan.');
            redirect('users');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Flash::withInput($in, ['username' => 'Username atau nomor identitas sudah terdaftar.']);
                redirect('users-edit', ['id' => $id]);
            }
            $this->fail($e, 'users-edit', ['id' => $id]);
        }
    }

    public function delete(): void
    {
        $id = $this->id();
        try {
            $um = new UserModel();
            $row = $um->find($id);
            if (!$row) {
                throw new BusinessException('Pengguna tidak ditemukan.');
            }
            if ($id === Auth::id()) {
                throw new BusinessException('Anda tidak dapat menghapus akun sendiri.');
            }
            if ($um->hasHistory($id)) {
                throw new BusinessException('Pengguna tidak dapat dihapus karena memiliki riwayat transaksi. Nonaktifkan akunnya sebagai gantinya.');
            }
            $um->delete($id);
            $this->log('DELETE', 'users', $id, 'menghapus pengguna ' . $row['username']);
            Flash::set('success', 'Data berhasil dihapus.');
        } catch (Throwable $e) {
            $this->fail($e, 'users');
        }
        redirect('users');
    }
}
