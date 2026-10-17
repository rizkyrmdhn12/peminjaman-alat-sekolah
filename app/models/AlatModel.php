<?php
class AlatModel extends Model
{
    public function paginate(array $f, int $page): array
    {
        $where = [];
        $par = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(a.kode_inventaris LIKE ? OR a.nama_alat LIKE ? OR a.merek LIKE ? OR a.tipe_model LIKE ?)';
            $l = $this->like($f['q']);
            array_push($par, $l, $l, $l, $l);
        }
        if (($f['id_kategori'] ?? '') !== '') {
            $where[] = 'a.id_kategori = ?';
            $par[] = (int)$f['id_kategori'];
        }
        foreach (['status_alat', 'kondisi'] as $k) {
            if (($f[$k] ?? '') !== '') {
                $where[] = "a.$k = ?";
                $par[] = $f[$k];
            }
        }
        return $this->paged(
            'a.id_alat, a.kode_inventaris, a.nama_alat, a.merek, a.tipe_model, a.kondisi, a.status_alat, a.lokasi_penyimpanan, k.nama_kategori',
            'alat a JOIN kategori_alat k ON k.id_kategori = a.id_kategori',
            $where, $par, 'k.nama_kategori ASC, a.kode_inventaris ASC', $page
        );
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT a.id_alat, a.id_kategori, a.kode_inventaris, a.nama_alat, a.merek, a.tipe_model, a.deskripsi, a.kondisi, a.status_alat, a.lokasi_penyimpanan, a.created_at, a.updated_at, k.nama_kategori FROM alat a JOIN kategori_alat k ON k.id_kategori = a.id_kategori WHERE a.id_alat = ?', [$id]);
    }

    /** Alat yang bisa dipilih di form peminjaman. */
    public function tersedia(): array
    {
        return $this->all("SELECT a.id_alat, a.kode_inventaris, a.nama_alat, a.merek, a.tipe_model, k.nama_kategori FROM alat a JOIN kategori_alat k ON k.id_kategori = a.id_kategori WHERE a.status_alat = 'tersedia' ORDER BY k.nama_kategori, a.nama_alat, a.kode_inventaris");
    }

    /** Daftar ringkas seluruh alat untuk dropdown filter. */
    public function opsi(): array
    {
        return $this->all('SELECT id_alat, kode_inventaris, nama_alat FROM alat ORDER BY kode_inventaris');
    }

    public function countByStatus(): array
    {
        $out = ['tersedia' => 0, 'dipinjam' => 0, 'rusak' => 0, 'hilang' => 0];
        foreach ($this->all('SELECT status_alat, COUNT(*) AS c FROM alat GROUP BY status_alat') as $r) {
            $out[$r['status_alat']] = (int)$r['c'];
        }
        return $out;
    }

    public function kodeTaken(string $kode, ?int $except = null): bool
    {
        return (int)$this->val('SELECT COUNT(*) FROM alat WHERE kode_inventaris = ? AND id_alat <> ?', [$kode, $except ?? 0]) > 0;
    }

    public function hasHistory(int $id): bool
    {
        return (int)$this->val('SELECT COUNT(*) FROM detail_peminjaman WHERE id_alat = ?', [$id]) > 0;
    }

    /** Peminjaman aktif terakhir dari sebuah alat (hanya untuk admin/petugas). */
    public function peminjamanAktif(int $id): ?array
    {
        return $this->one("SELECT p.id_peminjaman, p.kode_peminjaman, p.batas_kembali, p.status_peminjaman, u.nama_lengkap AS nama_peminjam FROM detail_peminjaman d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman JOIN users u ON u.id_user = p.id_peminjam WHERE d.id_alat = ? AND p.status_peminjaman IN ('dipinjam','terlambat','hilang') ORDER BY p.id_peminjaman DESC LIMIT 1", [$id]);
    }

    public function riwayat(int $id, int $limit = 5): array
    {
        return $this->all('SELECT p.id_peminjaman, p.kode_peminjaman, p.tanggal_peminjaman, p.status_peminjaman, u.nama_lengkap AS nama_peminjam FROM detail_peminjaman d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman JOIN users u ON u.id_user = p.id_peminjam WHERE d.id_alat = ? ORDER BY p.id_peminjaman DESC LIMIT ?', [$id, $limit]);
    }

    public function create(array $d): int
    {
        $this->exec('INSERT INTO alat (id_kategori, kode_inventaris, nama_alat, merek, tipe_model, deskripsi, kondisi, status_alat, lokasi_penyimpanan) VALUES (?,?,?,?,?,?,?,?,?)', [
            (int)$d['id_kategori'], $d['kode_inventaris'], $d['nama_alat'], $d['merek'], $d['tipe_model'],
            $d['deskripsi'], $d['kondisi'], $d['status_alat'], $d['lokasi_penyimpanan'],
        ]);
        return $this->insertId();
    }

    public function update(int $id, array $d): void
    {
        $this->exec('UPDATE alat SET id_kategori = ?, kode_inventaris = ?, nama_alat = ?, merek = ?, tipe_model = ?, deskripsi = ?, kondisi = ?, status_alat = ?, lokasi_penyimpanan = ? WHERE id_alat = ?', [
            (int)$d['id_kategori'], $d['kode_inventaris'], $d['nama_alat'], $d['merek'], $d['tipe_model'],
            $d['deskripsi'], $d['kondisi'], $d['status_alat'], $d['lokasi_penyimpanan'], $id,
        ]);
    }

    public function delete(int $id): int
    {
        return $this->exec('DELETE FROM alat WHERE id_alat = ?', [$id]);
    }
}
