<?php
class KelasModel extends Model
{
    public function aktif(): array
    {
        return $this->all("SELECT id_kelas, id_kelompok_jadwal, nama_kelas FROM kelas WHERE status = 'aktif' ORDER BY tingkat, nama_kelas");
    }

    public function findAktif(int $id): ?array
    {
        return $this->one("SELECT id_kelas, id_kelompok_jadwal, nama_kelas FROM kelas WHERE id_kelas = ? AND status = 'aktif'", [$id]);
    }
}
