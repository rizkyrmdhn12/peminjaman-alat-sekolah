<?php
class LaporanController extends Controller
{
    private const STATUS_PINJAM = ['diajukan', 'disetujui', 'ditolak', 'dipinjam', 'terlambat', 'hilang', 'dikembalikan', 'dibatalkan'];

    private function filters(): array
    {
        $jenis = $this->enumGet('jenis', LaporanModel::JENIS) ?: 'peminjaman';
        $statusList = $jenis === 'peminjaman' ? self::STATUS_PINJAM : ($jenis === 'denda' ? ['belum_diselesaikan', 'diselesaikan'] : []);
        return [
            'jenis' => $jenis,
            'dari' => $this->dateGet('dari'),
            'sampai' => $this->dateGet('sampai'),
            'status' => $this->enumGet('status', $statusList),
            'jenis_peminjaman' => $this->enumGet('jenis_peminjaman', ['online', 'offline']),
            'jenis_denda' => $jenis === 'denda' ? $this->enumGet('jenis_denda', ['keterlambatan', 'kerusakan', 'kehilangan']) : '',
            'peminjam' => mb_substr($this->get('peminjam'), 0, 50),
            'id_alat' => ctype_digit($this->get('id_alat')) ? $this->get('id_alat') : '',
            'id_kategori' => ctype_digit($this->get('id_kategori')) ? $this->get('id_kategori') : '',
        ];
    }

    private function statusList(string $jenis): array
    {
        return $jenis === 'peminjaman' ? self::STATUS_PINJAM : ($jenis === 'denda' ? ['belum_diselesaikan', 'diselesaikan'] : []);
    }

    public function index(): void
    {
        $f = $this->filters();
        $this->render('laporan/index', [
            'title' => 'Laporan',
            'f' => $f,
            'statusList' => $this->statusList($f['jenis']),
            'pg' => (new LaporanModel())->query($f['jenis'], $f, $this->page(), PER_PAGE),
            'alatList' => (new AlatModel())->opsi(),
            'kategori' => (new KategoriModel())->semua(),
        ]);
    }

    public function cetak(): void
    {
        $f = $this->filters();
        $res = (new LaporanModel())->query($f['jenis'], $f, 1, 1000);
        $this->render('laporan/print', [
            'title' => 'Laporan ' . ucfirst($f['jenis']),
            'f' => $f,
            'pg' => $res,
            'dicetakOleh' => Auth::user()['nama_lengkap'] ?? '',
        ], 'print');
    }
}
