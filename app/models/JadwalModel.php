<?php
class JadwalModel extends Model
{
    public function kelompokList(): array
    {
        return $this->all('SELECT id_kelompok_jadwal, nama_kelompok, keterangan FROM kelompok_jadwal ORDER BY id_kelompok_jadwal');
    }

    public function kelompokExists(int $id): bool
    {
        return (int)$this->val('SELECT COUNT(*) FROM kelompok_jadwal WHERE id_kelompok_jadwal = ?', [$id]) > 0;
    }

    /** Kelompok jadwal milik user: dari kelasnya (siswa). Guru/staff => null (memilih sendiri). */
    public function kelompokByKelas(?int $idKelas): ?int
    {
        if (!$idKelas) return null;
        $v = $this->val('SELECT id_kelompok_jadwal FROM kelas WHERE id_kelas = ?', [$idKelas]);
        return $v === null ? null : (int)$v;
    }

    public function jpHari(int $idKelompok, string $hari): array
    {
        return $this->all("SELECT id_jadwal, nomor_jp, jam_mulai, jam_selesai FROM jadwal_kbm WHERE id_kelompok_jadwal = ? AND hari = ? AND status = 'aktif' ORDER BY nomor_jp", [$idKelompok, $hari]);
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT id_jadwal, id_kelompok_jadwal, hari, nomor_jp, jam_mulai, jam_selesai, status FROM jadwal_kbm WHERE id_jadwal = ?', [$id]);
    }
}
