<?php
/** Aturan jadwal JP: tanggal SELALU dari server, jam dari tabel jadwal_kbm (bukan input manual). */
final class JadwalService
{
    private const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

    public static function hariIni(): ?string
    {
        return self::HARI[(int)date('N')] ?? null;
    }

    /** JP hari ini yang belum selesai, untuk pilihan di form. */
    public static function opsiJp(int $idKelompok): array
    {
        $hari = self::hariIni();
        if ($hari === null) {
            return [];
        }
        $now = date('H:i:s');
        $out = [];
        foreach ((new JadwalModel())->jpHari($idKelompok, $hari) as $jp) {
            if ($jp['jam_selesai'] > $now) {
                $out[] = [
                    'id' => (int)$jp['id_jadwal'],
                    'nomor' => (int)$jp['nomor_jp'],
                    'mulai' => jam($jp['jam_mulai']),
                    'selesai' => jam($jp['jam_selesai']),
                ];
            }
        }
        return $out;
    }

    /**
     * Validasi pilihan JP di server lalu hitung waktu_mulai & batas_kembali.
     * Contoh: JP 1 (08:05) s/d JP 3 (09:50) Senin => 08:05 - 09:50 pada tanggal hari ini.
     */
    public static function resolve(int $idMulai, int $idKembali, int $idKelompok): array
    {
        $hari = self::hariIni();
        if ($hari === null) {
            throw new BusinessException('Peminjaman hanya dapat dilakukan pada hari KBM (Senin-Jumat).');
        }
        $jm = new JadwalModel();
        $a = $jm->find($idMulai);
        $b = $jm->find($idKembali);
        if (!$a || !$b || $a['status'] !== 'aktif' || $b['status'] !== 'aktif') {
            throw new BusinessException('Pilihan JP tidak valid.');
        }
        if ((int)$a['id_kelompok_jadwal'] !== $idKelompok || (int)$b['id_kelompok_jadwal'] !== $idKelompok) {
            throw new BusinessException('JP tidak sesuai dengan kelompok jadwal peminjam.');
        }
        if ($a['hari'] !== $hari || $b['hari'] !== $hari) {
            throw new BusinessException('Peminjaman hanya dapat diajukan untuk hari ini (' . $hari . ').');
        }
        if ((int)$b['nomor_jp'] < (int)$a['nomor_jp']) {
            throw new BusinessException('JP kembali tidak boleh lebih awal dari JP mulai.');
        }
        $tanggal = date('Y-m-d');
        $mulai = $tanggal . ' ' . $a['jam_mulai'];
        $batas = $tanggal . ' ' . $b['jam_selesai'];
        if (strtotime($batas) <= time()) {
            throw new BusinessException('JP kembali yang dipilih sudah berakhir. Pilih JP yang masih berjalan atau berikutnya.');
        }
        return [
            'tanggal' => $tanggal, 'hari' => $hari,
            'waktu_mulai' => $mulai, 'batas_kembali' => $batas,
            'jp_mulai' => (int)$a['nomor_jp'], 'jp_kembali' => (int)$b['nomor_jp'],
        ];
    }
}
