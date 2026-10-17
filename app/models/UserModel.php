<?php
class UserModel extends Model
{
    public function findAuth(int $id): ?array
    {
        return $this->one('SELECT id_user, username, nama_lengkap, role, status FROM users WHERE id_user = ?', [$id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->one('SELECT id_user, username, password, nama_lengkap, role, status FROM users WHERE username = ?', [$username]);
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT u.id_user, u.id_kelas, u.username, u.nama_lengkap, u.jenis_pengguna, u.nomor_identitas, u.no_hp, u.email, u.role, u.status, k.nama_kelas FROM users u LEFT JOIN kelas k ON k.id_kelas = u.id_kelas WHERE u.id_user = ?', [$id]);
    }

    public function paginate(array $f, int $page): array
    {
        $where = [];
        $par = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(u.username LIKE ? OR u.nama_lengkap LIKE ? OR u.nomor_identitas LIKE ?)';
            $l = $this->like($f['q']);
            array_push($par, $l, $l, $l);
        }
        foreach (['role', 'status'] as $k) {
            if (($f[$k] ?? '') !== '') {
                $where[] = "u.$k = ?";
                $par[] = $f[$k];
            }
        }
        return $this->paged(
            'u.id_user, u.username, u.nama_lengkap, u.jenis_pengguna, u.nomor_identitas, u.no_hp, u.email, u.role, u.status, k.nama_kelas',
            'users u LEFT JOIN kelas k ON k.id_kelas = u.id_kelas',
            $where, $par, 'u.id_user DESC', $page
        );
    }

    /** Peminjam aktif untuk transaksi offline (id_kelas dipakai menentukan kelompok jadwal). */
    public function peminjamAktif(): array
    {
        return $this->all("SELECT u.id_user, u.nama_lengkap, u.username, u.jenis_pengguna, u.id_kelas, k.nama_kelas, k.id_kelompok_jadwal FROM users u LEFT JOIN kelas k ON k.id_kelas = u.id_kelas WHERE u.role = 'peminjam' AND u.status = 'aktif' ORDER BY u.nama_lengkap");
    }

    public function findPeminjamAktif(int $id): ?array
    {
        return $this->one("SELECT id_user, id_kelas, nama_lengkap FROM users WHERE id_user = ? AND role = 'peminjam' AND status = 'aktif'", [$id]);
    }

    public function usernameTaken(string $u, ?int $except = null): bool
    {
        return (int)$this->val('SELECT COUNT(*) FROM users WHERE username = ? AND id_user <> ?', [$u, $except ?? 0]) > 0;
    }

    public function identitasTaken(string $n, ?int $except = null): bool
    {
        return (int)$this->val('SELECT COUNT(*) FROM users WHERE nomor_identitas = ? AND id_user <> ?', [$n, $except ?? 0]) > 0;
    }

    public function hasHistory(int $id): bool
    {
        $c = (int)$this->val('SELECT (SELECT COUNT(*) FROM peminjaman WHERE id_peminjam = ? OR id_petugas = ?) + (SELECT COUNT(*) FROM pengembalian WHERE id_petugas = ?)', [$id, $id, $id]);
        return $c > 0;
    }

    public function create(array $d): int
    {
        $this->exec('INSERT INTO users (id_kelas, username, password, nama_lengkap, jenis_pengguna, nomor_identitas, no_hp, email, role, status) VALUES (?,?,?,?,?,?,?,?,?,?)', [
            $d['id_kelas'], $d['username'], $d['password'], $d['nama_lengkap'], $d['jenis_pengguna'],
            $d['nomor_identitas'], $d['no_hp'], $d['email'], $d['role'], $d['status'],
        ]);
        return $this->insertId();
    }

    public function update(int $id, array $d, ?string $passwordHash): void
    {
        $sql = 'UPDATE users SET id_kelas = ?, username = ?, nama_lengkap = ?, jenis_pengguna = ?, nomor_identitas = ?, no_hp = ?, email = ?, role = ?, status = ?';
        $par = [$d['id_kelas'], $d['username'], $d['nama_lengkap'], $d['jenis_pengguna'], $d['nomor_identitas'], $d['no_hp'], $d['email'], $d['role'], $d['status']];
        if ($passwordHash !== null) {
            $sql .= ', password = ?';
            $par[] = $passwordHash;
        }
        $this->exec($sql . ' WHERE id_user = ?', array_merge($par, [$id]));
    }

    public function delete(int $id): int
    {
        return $this->exec('DELETE FROM users WHERE id_user = ?', [$id]);
    }

    /** Validasi bersama untuk register (peminjam saja) dan form admin. */
    public function validate(array $in, ?int $exceptId, bool $needPassword, bool $adminForm): array
    {
        $role = $adminForm ? ($in['role'] ?? '') : 'peminjam';
        $rules = [
            'username' => 'required|min:4|max:30|username',
            'nama_lengkap' => 'required|min:3|max:100',
            'jenis_pengguna' => ($role === 'peminjam' ? 'required|' : '') . 'in:siswa,guru,staff',
            'nomor_identitas' => ($role === 'peminjam' ? 'required|' : '') . 'max:30|kode',
            'no_hp' => 'max:20|phone',
            'email' => 'max:100|email',
        ];
        $labels = ['nama_lengkap' => 'Nama lengkap', 'jenis_pengguna' => 'Jenis pengguna', 'nomor_identitas' => 'Nomor identitas', 'no_hp' => 'Nomor HP'];
        if ($adminForm) {
            $rules['role'] = 'required|in:admin,petugas,peminjam';
            $rules['status'] = 'required|in:aktif,nonaktif';
        }
        $err = Validator::run($in, $rules, $labels);

        $pw = (string)($in['password'] ?? '');
        if ($needPassword || $pw !== '') {
            if (mb_strlen($pw) < 8) {
                $err['password'] = 'Password minimal 8 karakter.';
            } elseif (strlen($pw) > 72) {
                $err['password'] = 'Password maksimal 72 karakter.';
            } elseif ($pw !== (string)($in['password_confirm'] ?? '')) {
                $err['password_confirm'] = 'Konfirmasi password tidak sama.';
            }
        }
        if (!isset($err['username']) && $this->usernameTaken((string)$in['username'], $exceptId)) {
            $err['username'] = 'Username sudah digunakan.';
        }
        $nomor = (string)($in['nomor_identitas'] ?? '');
        if ($nomor !== '' && !isset($err['nomor_identitas']) && $this->identitasTaken($nomor, $exceptId)) {
            $err['nomor_identitas'] = 'Nomor identitas sudah terdaftar.';
        }
        if (($in['jenis_pengguna'] ?? '') === 'siswa' && !isset($err['jenis_pengguna'])) {
            $k = (string)($in['id_kelas'] ?? '');
            if ($k === '' || !ctype_digit($k) || !(new KelasModel())->findAktif((int)$k)) {
                $err['id_kelas'] = 'Kelas wajib dipilih untuk siswa.';
            }
        }
        return $err;
    }
}
