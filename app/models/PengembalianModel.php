<?php
class PengembalianModel extends Model
{
    public function riwayat(array $f, int $page): array
    {
        $where = [];
        $par = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(p.kode_peminjaman LIKE ? OR u.nama_lengkap LIKE ?)';
            $l = $this->like($f['q']);
            array_push($par, $l, $l);
        }
        if (($f['dari'] ?? '') !== '') {
            $where[] = 'pg.tanggal_pengembalian >= ?';
            $par[] = $f['dari'] . ' 00:00:00';
        }
        if (($f['sampai'] ?? '') !== '') {
            $where[] = 'pg.tanggal_pengembalian < ?';
            $par[] = date('Y-m-d', strtotime($f['sampai'] . ' +1 day')) . ' 00:00:00';
        }
        return $this->paged(
            'pg.id_pengembalian, pg.tanggal_pengembalian, pg.total_denda, p.id_peminjaman, p.kode_peminjaman, u.nama_lengkap AS nama_peminjam, pt.nama_lengkap AS nama_petugas, (SELECT COUNT(*) FROM detail_pengembalian x WHERE x.id_pengembalian = pg.id_pengembalian) AS jumlah_alat',
            'pengembalian pg JOIN peminjaman p ON p.id_peminjaman = pg.id_peminjaman JOIN users u ON u.id_user = p.id_peminjam LEFT JOIN users pt ON pt.id_user = pg.id_petugas',
            $where, $par, 'pg.id_pengembalian DESC', $page
        );
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT pg.id_pengembalian, pg.id_peminjaman, pg.tanggal_pengembalian, pg.total_denda, pg.catatan, p.kode_peminjaman, p.batas_kembali, p.status_peminjaman, u.nama_lengkap AS nama_peminjam, pt.nama_lengkap AS nama_petugas FROM pengembalian pg JOIN peminjaman p ON p.id_peminjaman = pg.id_peminjaman JOIN users u ON u.id_user = p.id_peminjam LEFT JOIN users pt ON pt.id_user = pg.id_petugas WHERE pg.id_pengembalian = ?', [$id]);
    }

    public function findByPeminjaman(int $idPeminjaman): ?array
    {
        return $this->one('SELECT id_pengembalian, tanggal_pengembalian, total_denda FROM pengembalian WHERE id_peminjaman = ?', [$idPeminjaman]);
    }

    public function items(int $idPengembalian): array
    {
        return $this->all('SELECT x.kondisi, x.jumlah_jp_terlambat, x.denda_keterlambatan, x.denda_kerusakan, x.denda_kehilangan, x.catatan, a.kode_inventaris, a.nama_alat, a.merek FROM detail_pengembalian x JOIN detail_peminjaman d ON d.id_detail_peminjaman = x.id_detail_peminjaman JOIN alat a ON a.id_alat = d.id_alat WHERE x.id_pengembalian = ? ORDER BY a.nama_alat, a.kode_inventaris', [$idPengembalian]);
    }

    public function dendaByPeminjaman(int $idPeminjaman): array
    {
        return $this->all('SELECT dn.id_denda, dn.jenis_denda, dn.jumlah_denda, dn.keterangan, dn.status_denda, dn.tanggal_denda, a.kode_inventaris, a.nama_alat FROM denda dn JOIN detail_peminjaman d ON d.id_detail_peminjaman = dn.id_detail_peminjaman JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = ? ORDER BY dn.id_denda', [$idPeminjaman]);
    }

    /** Estimasi JP terlambat memakai function SQL yang sama dengan procedure (satu sumber kebenaran). */
    public function previewJp(int $idPeminjaman, string $waktu): int
    {
        return (int)$this->val('SELECT fn_hitung_jp_terlambat(?, ?)', [$idPeminjaman, $waktu]);
    }

    /**
     * Proses pengembalian via stored procedure (transaksi + denda + status alat/peminjaman ada di DB).
     * $kondisi: [id_detail_peminjaman => ['kondisi' => ..., 'catatan' => ...]]
     */
    public function proses(int $idPeminjaman, int $idPetugas, array $kondisi, ?string $catatan, string $waktu): int
    {
        $this->db->exec('DROP TEMPORARY TABLE IF EXISTS tmp_kondisi_kembali');
        $this->db->exec('CREATE TEMPORARY TABLE tmp_kondisi_kembali (id_detail_peminjaman BIGINT UNSIGNED NOT NULL PRIMARY KEY, kondisi VARCHAR(20) NOT NULL, catatan TEXT NULL) ENGINE=InnoDB');
        try {
            foreach ($kondisi as $idDetail => $k) {
                $this->exec('INSERT INTO tmp_kondisi_kembali (id_detail_peminjaman, kondisi, catatan) VALUES (?,?,?)', [(int)$idDetail, $k['kondisi'], nz($k['catatan'] ?? '')]);
            }
            $st = $this->db->prepare('CALL sp_proses_pengembalian(?, ?, ?, ?, @id_pengembalian)');
            $st->execute([$idPeminjaman, $idPetugas, $waktu, $catatan]);
            $st->closeCursor();
            return (int)$this->db->query('SELECT @id_pengembalian')->fetchColumn();
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? '') === '45000') {
                throw new BusinessException((string)($e->errorInfo[2] ?? 'Pengembalian tidak dapat diproses.'));
            }
            throw $e;
        } finally {
            $this->db->exec('DROP TEMPORARY TABLE IF EXISTS tmp_kondisi_kembali');
        }
    }

    public function updateCatatan(int $id, ?string $catatan): void
    {
        $this->exec('UPDATE pengembalian SET catatan = ? WHERE id_pengembalian = ?', [$catatan, $id]);
    }

    /**
     * Koreksi salah input: hanya pengembalian TANPA denda & semua alat kondisi baik.
     * Pengembalian ber-denda tidak boleh dihapus (histori denda dipertahankan).
     */
    public function batalkanTanpaDenda(int $id): int
    {
        return $this->tx(function () use ($id) {
            $pg = $this->one('SELECT id_pengembalian, id_peminjaman, total_denda FROM pengembalian WHERE id_pengembalian = ? FOR UPDATE', [$id]);
            if (!$pg) {
                throw new BusinessException('Data pengembalian tidak ditemukan.');
            }
            $adaDenda = (int)$this->val('SELECT COUNT(*) FROM denda d JOIN detail_peminjaman dp ON dp.id_detail_peminjaman = d.id_detail_peminjaman WHERE dp.id_peminjaman = ?', [(int)$pg['id_peminjaman']]);
            if ((float)$pg['total_denda'] > 0 || $adaDenda > 0) {
                throw new BusinessException('Pengembalian yang memiliki denda tidak dapat dihapus karena histori denda harus dipertahankan.');
            }
            $bad = (int)$this->val("SELECT COUNT(*) FROM detail_pengembalian x JOIN detail_peminjaman d ON d.id_detail_peminjaman = x.id_detail_peminjaman JOIN alat a ON a.id_alat = d.id_alat WHERE x.id_pengembalian = ? AND (x.kondisi <> 'baik' OR a.status_alat <> 'tersedia')", [$id]);
            if ($bad > 0) {
                throw new BusinessException('Status alat sudah berubah sejak dikembalikan, sehingga pengembalian ini tidak dapat dibatalkan.');
            }
            $this->exec("UPDATE alat a JOIN detail_peminjaman d ON d.id_alat = a.id_alat JOIN detail_pengembalian x ON x.id_detail_peminjaman = d.id_detail_peminjaman SET a.status_alat = 'dipinjam' WHERE x.id_pengembalian = ?", [$id]);
            $this->exec('DELETE FROM pengembalian WHERE id_pengembalian = ?', [$id]);
            $this->exec("UPDATE peminjaman SET status_peminjaman = 'dipinjam', waktu_dikembalikan = NULL WHERE id_peminjaman = ?", [(int)$pg['id_peminjaman']]);
            return (int)$pg['id_peminjaman'];
        });
    }

    public function paginateDenda(array $f, int $page): array
    {
        $where = [];
        $par = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(p.kode_peminjaman LIKE ? OR u.nama_lengkap LIKE ? OR a.nama_alat LIKE ? OR a.kode_inventaris LIKE ?)';
            $l = $this->like($f['q']);
            array_push($par, $l, $l, $l, $l);
        }
        if (($f['status'] ?? '') !== '') {
            $where[] = 'dn.status_denda = ?';
            $par[] = $f['status'];
        }
        if (($f['jenis'] ?? '') !== '') {
            $where[] = 'dn.jenis_denda = ?';
            $par[] = $f['jenis'];
        }
        return $this->paged(
            'dn.id_denda, dn.jenis_denda, dn.jumlah_denda, dn.keterangan, dn.status_denda, dn.tanggal_denda, a.kode_inventaris, a.nama_alat, p.id_peminjaman, p.kode_peminjaman, u.nama_lengkap AS nama_peminjam',
            'denda dn JOIN detail_peminjaman d ON d.id_detail_peminjaman = dn.id_detail_peminjaman JOIN alat a ON a.id_alat = d.id_alat JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman JOIN users u ON u.id_user = p.id_peminjam',
            $where, $par, 'dn.id_denda DESC', $page
        );
    }

    public function dendaInfo(int $idDenda): ?array
    {
        return $this->one('SELECT id_denda, jenis_denda, jumlah_denda, status_denda FROM denda WHERE id_denda = ?', [$idDenda]);
    }

    public function selesaikanDenda(int $idDenda): bool
    {
        return $this->exec("UPDATE denda SET status_denda = 'diselesaikan' WHERE id_denda = ? AND status_denda = 'belum_diselesaikan'", [$idDenda]) > 0;
    }

    public function dendaBelumSelesai(): array
    {
        $r = $this->one("SELECT COUNT(*) AS jumlah, COALESCE(SUM(jumlah_denda), 0) AS total FROM denda WHERE status_denda = 'belum_diselesaikan'");
        return ['jumlah' => (int)$r['jumlah'], 'total' => (float)$r['total']];
    }

    public function jumlahHariIni(): int
    {
        return (int)$this->val('SELECT COUNT(*) FROM pengembalian WHERE tanggal_pengembalian >= ? AND tanggal_pengembalian < ?', [date('Y-m-d') . ' 00:00:00', date('Y-m-d', strtotime('+1 day')) . ' 00:00:00']);
    }
}
