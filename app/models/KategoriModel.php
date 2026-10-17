<?php
class KategoriModel extends Model
{
    public function semua(): array
    {
        return $this->all('SELECT id_kategori, nama_kategori FROM kategori_alat ORDER BY nama_kategori');
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT id_kategori, nama_kategori, deskripsi FROM kategori_alat WHERE id_kategori = ?', [$id]);
    }

    public function paginate(string $q, int $page): array
    {
        $where = [];
        $par = [];
        if ($q !== '') {
            $where[] = '(k.nama_kategori LIKE ? OR k.deskripsi LIKE ?)';
            $l = $this->like($q);
            array_push($par, $l, $l);
        }
        return $this->paged(
            'k.id_kategori, k.nama_kategori, k.deskripsi, (SELECT COUNT(*) FROM alat a WHERE a.id_kategori = k.id_kategori) AS jumlah_alat',
            'kategori_alat k', $where, $par, 'k.nama_kategori ASC', $page
        );
    }

    public function nameTaken(string $n, ?int $except = null): bool
    {
        return (int)$this->val('SELECT COUNT(*) FROM kategori_alat WHERE nama_kategori = ? AND id_kategori <> ?', [$n, $except ?? 0]) > 0;
    }

    public function usedCount(int $id): int
    {
        return (int)$this->val('SELECT COUNT(*) FROM alat WHERE id_kategori = ?', [$id]);
    }

    public function create(string $nama, ?string $desk): int
    {
        $this->exec('INSERT INTO kategori_alat (nama_kategori, deskripsi) VALUES (?, ?)', [$nama, $desk]);
        return $this->insertId();
    }

    public function update(int $id, string $nama, ?string $desk): void
    {
        $this->exec('UPDATE kategori_alat SET nama_kategori = ?, deskripsi = ? WHERE id_kategori = ?', [$nama, $desk, $id]);
    }

    public function delete(int $id): int
    {
        return $this->exec('DELETE FROM kategori_alat WHERE id_kategori = ?', [$id]);
    }
}
