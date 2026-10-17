<?php
/** Laporan peminjaman / pengembalian / denda dengan filter tanggal, status, jenis, peminjam, alat, kategori. */
class LaporanModel extends Model
{
    public const JENIS = ['peminjaman', 'pengembalian', 'denda'];

    public function query(string $jenis, array $f, int $page, int $per): array
    {
        [$select, $from, $where, $par, $order, $sum] = $this->build($jenis, $f);
        $res = $this->paged($select, $from, $where, $par, $order, $page, $per);
        $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $res['total_denda'] = $sum ? (float)$this->val("SELECT COALESCE(SUM($sum), 0) FROM $from$w", $par) : null;
        return $res;
    }

    private function build(string $jenis, array $f): array
    {
        $where = [];
        $par = [];
        $alatExists = function (string $col) use (&$where, &$par, $f) {
            if (($f['id_alat'] ?? '') !== '') {
                $where[] = "EXISTS (SELECT 1 FROM detail_peminjaman x WHERE x.id_peminjaman = p.id_peminjaman AND x.id_alat = ?)";
                $par[] = (int)$f['id_alat'];
            }
            if (($f['id_kategori'] ?? '') !== '') {
                $where[] = "EXISTS (SELECT 1 FROM detail_peminjaman x JOIN alat ax ON ax.id_alat = x.id_alat WHERE x.id_peminjaman = p.id_peminjaman AND ax.id_kategori = ?)";
                $par[] = (int)$f['id_kategori'];
            }
        };
        $tgl = function (string $col) use (&$where, &$par, $f) {
            if (($f['dari'] ?? '') !== '') {
                $where[] = "$col >= ?";
                $par[] = $f['dari'] . ' 00:00:00';
            }
            if (($f['sampai'] ?? '') !== '') {
                $where[] = "$col < ?";
                $par[] = date('Y-m-d', strtotime($f['sampai'] . ' +1 day')) . ' 00:00:00';
            }
        };
        $peminjam = function () use (&$where, &$par, $f) {
            if (($f['peminjam'] ?? '') !== '') {
                $where[] = '(u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.nomor_identitas LIKE ?)';
                $l = $this->like($f['peminjam']);
                array_push($par, $l, $l, $l);
            }
        };
        $daftar = "(SELECT GROUP_CONCAT(a.nama_alat ORDER BY a.nama_alat SEPARATOR ', ') FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = p.id_peminjaman) AS daftar_alat";

        if ($jenis === 'pengembalian') {
            $tgl('pg.tanggal_pengembalian');
            $peminjam();
            $alatExists('');
            if (($f['jenis_peminjaman'] ?? '') !== '') {
                $where[] = 'p.jenis_peminjaman = ?';
                $par[] = $f['jenis_peminjaman'];
            }
            return [
                "pg.id_pengembalian, pg.tanggal_pengembalian, pg.total_denda, p.kode_peminjaman, p.jenis_peminjaman, p.batas_kembali, u.nama_lengkap AS nama_peminjam, pt.nama_lengkap AS nama_petugas, $daftar",
                'pengembalian pg JOIN peminjaman p ON p.id_peminjaman = pg.id_peminjaman JOIN users u ON u.id_user = p.id_peminjam LEFT JOIN users pt ON pt.id_user = pg.id_petugas',
                $where, $par, 'pg.tanggal_pengembalian DESC, pg.id_pengembalian DESC', 'pg.total_denda',
            ];
        }
        if ($jenis === 'denda') {
            $tgl('dn.tanggal_denda');
            $peminjam();
            if (($f['status'] ?? '') !== '') {
                $where[] = 'dn.status_denda = ?';
                $par[] = $f['status'];
            }
            if (($f['jenis_denda'] ?? '') !== '') {
                $where[] = 'dn.jenis_denda = ?';
                $par[] = $f['jenis_denda'];
            }
            if (($f['jenis_peminjaman'] ?? '') !== '') {
                $where[] = 'p.jenis_peminjaman = ?';
                $par[] = $f['jenis_peminjaman'];
            }
            if (($f['id_alat'] ?? '') !== '') {
                $where[] = 'a.id_alat = ?';
                $par[] = (int)$f['id_alat'];
            }
            if (($f['id_kategori'] ?? '') !== '') {
                $where[] = 'a.id_kategori = ?';
                $par[] = (int)$f['id_kategori'];
            }
            return [
                'dn.id_denda, dn.tanggal_denda, dn.jenis_denda, dn.jumlah_denda, dn.keterangan, dn.status_denda, a.kode_inventaris, a.nama_alat, p.kode_peminjaman, u.nama_lengkap AS nama_peminjam',
                'denda dn JOIN detail_peminjaman d ON d.id_detail_peminjaman = dn.id_detail_peminjaman JOIN alat a ON a.id_alat = d.id_alat JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman JOIN users u ON u.id_user = p.id_peminjam',
                $where, $par, 'dn.tanggal_denda DESC, dn.id_denda DESC', 'dn.jumlah_denda',
            ];
        }
        // peminjaman
        $tgl('p.waktu_mulai');
        $peminjam();
        $alatExists('');
        if (($f['status'] ?? '') !== '') {
            $where[] = 'p.status_peminjaman = ?';
            $par[] = $f['status'];
        }
        if (($f['jenis_peminjaman'] ?? '') !== '') {
            $where[] = 'p.jenis_peminjaman = ?';
            $par[] = $f['jenis_peminjaman'];
        }
        return [
            "p.id_peminjaman, p.kode_peminjaman, p.jenis_peminjaman, p.tanggal_peminjaman, p.waktu_mulai, p.batas_kembali, p.waktu_dikembalikan, p.status_peminjaman, u.nama_lengkap AS nama_peminjam, $daftar",
            'peminjaman p JOIN users u ON u.id_user = p.id_peminjam',
            $where, $par, 'p.waktu_mulai DESC, p.id_peminjaman DESC', null,
        ];
    }
}
