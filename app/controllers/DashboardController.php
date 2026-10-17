<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        redirect(Auth::homeRoute());
    }

    public function admin(): void
    {
        $pm = new PeminjamanModel();
        $pg = new PengembalianModel();
        $cnt = $pm->countByStatus();
        $this->render('dashboard/admin', [
            'title' => 'Dashboard Admin',
            'cnt' => $cnt,
            'alat' => (new AlatModel())->countByStatus(),
            'denda' => $pg->dendaBelumSelesai(),
            'kembaliHariIni' => $pg->jumlahHariIni(),
            'pending' => $pm->listByStatus(['diajukan'], 5, null, 'p.id_peminjaman ASC'),
            'aktif' => $pm->listByStatus(['dipinjam', 'terlambat', 'hilang'], 6),
            'logs' => (new LogModel())->recent(8),
        ]);
    }

    public function petugas(): void
    {
        $pm = new PeminjamanModel();
        $pg = new PengembalianModel();
        $this->render('dashboard/petugas', [
            'title' => 'Dashboard Petugas',
            'cnt' => $pm->countByStatus(),
            'kembaliHariIni' => $pg->jumlahHariIni(),
            'pending' => $pm->listByStatus(['diajukan'], 6, null, 'p.id_peminjaman ASC'),
            'aktif' => $pm->listByStatus(['dipinjam'], 6),
            'terlambat' => $pm->listByStatus(['terlambat'], 6),
            'hilang' => $pm->listByStatus(['hilang'], 6),
        ]);
    }

    public function peminjam(): void
    {
        $pm = new PeminjamanModel();
        $me = Auth::id();
        $this->render('dashboard/peminjam', [
            'title' => 'Dashboard',
            'cnt' => $pm->countByStatus($me),
            'aktif' => $pm->listByStatus(['dipinjam', 'terlambat', 'hilang'], 5, $me),
            'diajukan' => $pm->listByStatus(['diajukan'], 5, $me, 'p.id_peminjaman DESC'),
            'riwayat' => $pm->recent($me, 5),
            'hari' => JadwalService::hariIni(),
        ]);
    }
}
