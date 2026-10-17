<?php
class AuthController extends Controller
{
    private const DUMMY_HASH = '$2y$10$DummyHashForTimingXXXup1.joH1zmEnGv6MA00/UNwX9CNGSi9O';

    private function rawPost(string $k): string
    {
        return is_string($_POST[$k] ?? null) ? $_POST[$k] : '';
    }

    public function login(): void
    {
        if (Auth::check()) {
            redirect(Auth::homeRoute());
        }
        if (!$this->isPost()) {
            $this->render('auth/login', ['title' => 'Masuk'], 'public');
            return;
        }
        $username = mb_substr($this->post('username'), 0, 30);
        $password = $this->rawPost('password');
        $log = new LogModel();

        if ($log->loginGagal(clientIp(), LOGIN_JENDELA_MENIT) >= LOGIN_MAX_GAGAL) {
            Flash::set('error', 'Terlalu banyak percobaan login gagal. Coba lagi dalam ' . LOGIN_JENDELA_MENIT . ' menit.');
            redirect('login');
        }
        $u = $username !== '' ? (new UserModel())->findByUsername($username) : null;
        // password_verify tetap dijalankan walau user tidak ada (menyamakan waktu respons)
        $passOk = password_verify($password, $u['password'] ?? self::DUMMY_HASH);
        if (!$u || !$passOk) {
            $log->add(null, 'LOGIN_GAGAL', 'auth', null, 'username: ' . $username);
            Flash::withInput(['username' => $username]);
            Flash::set('error', 'Username atau password salah.');
            redirect('login');
        }
        if ($u['status'] !== 'aktif') {
            Flash::set('error', 'Akun Anda tidak aktif. Hubungi admin sekolah.');
            redirect('login');
        }
        Auth::login($u);
        $log->add((int)$u['id_user'], 'LOGIN', 'auth', (int)$u['id_user'], 'berhasil login');
        Flash::set('success', 'Selamat datang, ' . $u['nama_lengkap'] . '.');
        redirect(Auth::homeRoute());
    }

    public function register(): void
    {
        if (Auth::check()) {
            redirect(Auth::homeRoute());
        }
        if (!$this->isPost()) {
            $this->render('auth/register', ['title' => 'Daftar', 'kelas' => (new KelasModel())->aktif()], 'public');
            return;
        }
        // Role TIDAK dibaca dari input: pendaftaran publik selalu 'peminjam'.
        $in = $this->only(['username', 'nama_lengkap', 'jenis_pengguna', 'id_kelas', 'nomor_identitas', 'no_hp', 'email']);
        $in['password'] = $this->rawPost('password');
        $in['password_confirm'] = $this->rawPost('password_confirm');
        $um = new UserModel();
        $err = $um->validate($in, null, true, false);
        if ($err) {
            Flash::withInput($in, $err);
            redirect('register');
        }
        try {
            $id = $um->create([
                'id_kelas' => $in['jenis_pengguna'] === 'siswa' ? (int)$in['id_kelas'] : null,
                'username' => $in['username'],
                'password' => password_hash($in['password'], PASSWORD_DEFAULT),
                'nama_lengkap' => $in['nama_lengkap'],
                'jenis_pengguna' => $in['jenis_pengguna'],
                'nomor_identitas' => nz($in['nomor_identitas']),
                'no_hp' => nz($in['no_hp']),
                'email' => nz($in['email']),
                'role' => 'peminjam',
                'status' => 'aktif',
            ]);
            (new LogModel())->add($id, 'CREATE', 'users', $id, 'registrasi peminjam ' . $in['username']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Flash::withInput($in, ['username' => 'Username atau nomor identitas sudah terdaftar.']);
                redirect('register');
            }
            $this->fail($e, 'register');
        }
        Flash::set('success', 'Registrasi berhasil. Silakan login.');
        redirect('login');
    }

    public function logout(): void
    {
        $this->log('LOGOUT', 'auth', Auth::id(), 'logout');
        Auth::destroy();
        Flash::set('success', 'Anda telah keluar.');
        redirect('home');
    }
}
