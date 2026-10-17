<?php
class PeminjamanModel extends Model
{
    public const AKTIF = ['dipinjam', 'terlambat', 'hilang'];

    private const LIST_SELECT = "p.id_peminjaman, p.kode_peminjaman, p.jenis_peminjaman, p.tanggal_peminjaman, p.waktu_mulai, p.batas_kembali, p.waktu_dikembalikan, p.status_peminjaman, u.nama_lengkap AS nama_peminjam, (SELECT GROUP_CONCAT(a.nama_alat ORDER BY a.nama_alat SEPARATOR ', ') FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = p.id_peminjaman) AS daftar_alat";
    private const LIST_FROM = 'peminjaman p JOIN users u ON u.id_user = p.id_peminjam';

    /** $owner != null => HANYA milik user tersebut (proteksi IDOR di level query). */
    public function paginate(array $f, int $page, ?int $owner = null): array
    {
        $where = [];
        $par = [];
        if ($owner !== null) {
            $where[] = 'p.id_peminjam = ?';
            $par[] = $owner;
        }
        if (($f['q'] ?? '') !== '') {
            $where[] = '(p.kode_peminjaman LIKE ? OR u.nama_lengkap LIKE ? OR u.username LIKE ?)';
            $l = $this->like($f['q']);
            array_push($par, $l, $l, $l);
        }
        if (!empty($f['statuses'])) {
            $where[] = 'p.status_peminjaman IN (' . implode(',', array_fill(0, count($f['statuses']), '?')) . ')';
            $par = array_merge($par, $f['statuses']);
        } elseif (($f['status'] ?? '') !== '') {
            $where[] = 'p.status_peminjaman = ?';
            $par[] = $f['status'];
        }
        if (($f['jenis'] ?? '') !== '') {
            $where[] = 'p.jenis_peminjaman = ?';
            $par[] = $f['jenis'];
        }
        if (($f['tanggal'] ?? '') !== '') {
            $where[] = 'p.tanggal_peminjaman = ?';
            $par[] = $f['tanggal'];
        }
        return $this->paged(self::LIST_SELECT, self::LIST_FROM, $where, $par, 'p.id_peminjaman DESC', $page);
    }

    public function findFor(int $id, ?int $owner): ?array
    {
        $sql = 'SELECT p.id_peminjaman, p.kode_peminjaman, p.id_peminjam, p.id_petugas, p.jenis_peminjaman, p.tanggal_peminjaman, p.waktu_mulai, p.batas_kembali, p.waktu_dikembalikan, p.status_peminjaman, p.catatan, p.created_at, '
            . 'u.nama_lengkap AS nama_peminjam, u.username, u.jenis_pengguna, kl.nama_kelas, pt.nama_lengkap AS nama_petugas, '
            . 'jm.hari, jm.nomor_jp AS jp_mulai, jk.nomor_jp AS jp_kembali '
            . 'FROM peminjaman p JOIN users u ON u.id_user = p.id_peminjam '
            . 'LEFT JOIN kelas kl ON kl.id_kelas = u.id_kelas LEFT JOIN users pt ON pt.id_user = p.id_petugas '
            . 'JOIN jadwal_kbm jm ON jm.id_jadwal = p.id_jadwal_mulai JOIN jadwal_kbm jk ON jk.id_jadwal = p.id_jadwal_kembali '
            . 'WHERE p.id_peminjaman = ?';
        $par = [$id];
        if ($owner !== null) {
            $sql .= ' AND p.id_peminjam = ?';
            $par[] = $owner;
        }
        return $this->one($sql, $par);
    }

    public function exists(int $id): bool
    {
        return (int)$this->val('SELECT COUNT(*) FROM peminjaman WHERE id_peminjaman = ?', [$id]) > 0;
    }

    public function details(int $idPeminjaman): array
    {
        return $this->all('SELECT d.id_detail_peminjaman, d.kondisi_saat_dipinjam, a.id_alat, a.kode_inventaris, a.nama_alat, a.merek, a.tipe_model, a.status_alat FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = ? ORDER BY a.nama_alat, a.kode_inventaris', [$idPeminjaman]);
    }

    public function countByStatus(?int $owner = null): array
    {
        $sql = 'SELECT status_peminjaman, COUNT(*) AS c FROM peminjaman';
        $par = [];
        if ($owner !== null) {
            $sql .= ' WHERE id_peminjam = ?';
            $par[] = $owner;
        }
        $out = [];
        foreach ($this->all($sql . ' GROUP BY status_peminjaman', $par) as $r) {
            $out[$r['status_peminjaman']] = (int)$r['c'];
        }
        return $out;
    }

    public function listByStatus(array $statuses, int $limit, ?int $owner = null, string $order = 'p.batas_kembali ASC'): array
    {
        $in = implode(',', array_fill(0, count($statuses), '?'));
        $sql = 'SELECT ' . self::LIST_SELECT . ' FROM ' . self::LIST_FROM . " WHERE p.status_peminjaman IN ($in)";
        $par = $statuses;
        if ($owner !== null) {
            $sql .= ' AND p.id_peminjam = ?';
            $par[] = $owner;
        }
        $par[] = $limit;
        return $this->all($sql . " ORDER BY $order LIMIT ?", $par);
    }

    public function recent(int $owner, int $limit): array
    {
        return $this->all('SELECT ' . self::LIST_SELECT . ' FROM ' . self::LIST_FROM . ' WHERE p.id_peminjam = ? ORDER BY p.id_peminjaman DESC LIMIT ?', [$owner, $limit]);
    }

    private function nextKode(string $tanggal): string
    {
        $prefix = 'PJM-' . date('Ymd', strtotime($tanggal)) . '-';
        $last = $this->val('SELECT MAX(kode_peminjaman) FROM peminjaman WHERE kode_peminjaman LIKE ? FOR UPDATE', [$prefix . '%']);
        $n = $last ? ((int)substr((string)$last, -4)) + 1 : 1;
        return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    }

    /** Kunci baris alat (FOR UPDATE) dan pastikan semuanya tersedia - mencegah race condition. */
    private function lockAlat(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        if (!$ids) {
            throw new BusinessException('Pilih minimal satu alat.');
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->all("SELECT id_alat, kode_inventaris, nama_alat, kondisi, status_alat FROM alat WHERE id_alat IN ($in) ORDER BY id_alat FOR UPDATE", $ids);
        if (count($rows) !== count($ids)) {
            throw new BusinessException('Sebagian alat yang dipilih tidak ditemukan.');
        }
        foreach ($rows as $r) {
            if ($r['status_alat'] !== 'tersedia') {
                throw new BusinessException('Alat ' . $r['kode_inventaris'] . ' (' . $r['nama_alat'] . ') sedang tidak tersedia.');
            }
        }
        return $rows;
    }

    /**
     * Buat transaksi + detail dalam satu transaksi DB.
     * $h: id_peminjam, id_petugas|null, jenis, status ('diajukan'|'dipinjam'), tanggal, id_jadwal_mulai,
     *     id_jadwal_kembali, waktu_mulai, batas_kembali, catatan|null
     */
    public function buat(array $h, array $alatIds): array
    {
        for ($try = 0; $try < 3; $try++) {
            try {
                return $this->tx(function () use ($h, $alatIds) {
                    $alat = $this->lockAlat($alatIds);
                    $kode = $this->nextKode($h['tanggal']);
                    $this->exec('INSERT INTO peminjaman (kode_peminjaman, id_peminjam, id_petugas, jenis_peminjaman, tanggal_peminjaman, id_jadwal_mulai, id_jadwal_kembali, waktu_mulai, batas_kembali, status_peminjaman, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
                        $kode, (int)$h['id_peminjam'], $h['id_petugas'], $h['jenis'], $h['tanggal'],
                        (int)$h['id_jadwal_mulai'], (int)$h['id_jadwal_kembali'], $h['waktu_mulai'], $h['batas_kembali'],
                        $h['status'], $h['catatan'],
                    ]);
                    $id = $this->insertId();
                    foreach ($alat as $a) {
                        $this->exec('INSERT INTO detail_peminjaman (id_peminjaman, id_alat, kondisi_saat_dipinjam) VALUES (?,?,?)', [$id, (int)$a['id_alat'], $a['kondisi']]);
                    }
                    if ($h['status'] === 'dipinjam') {
                        $in = implode(',', array_fill(0, count($alat), '?'));
                        $this->exec("UPDATE alat SET status_alat = 'dipinjam' WHERE id_alat IN ($in)", array_map(fn($a) => (int)$a['id_alat'], $alat));
                    }
                    return ['id' => $id, 'kode' => $kode];
                });
            } catch (PDOException $e) {
                if ($e->getCode() === '23000' && strpos($e->getMessage(), 'uq_peminjaman_kode') !== false && $try < 2) {
                    continue;
                }
                throw $e;
            }
        }
        throw new BusinessException('Gagal membuat kode transaksi. Silakan coba lagi.');
    }

    public function approve(int $id, int $idPetugas): void
    {
        $this->tx(function () use ($id, $idPetugas) {
            $p = $this->one('SELECT id_peminjaman, status_peminjaman, batas_kembali FROM peminjaman WHERE id_peminjaman = ? FOR UPDATE', [$id]);
            if (!$p) {
                throw new BusinessException('Data peminjaman tidak ditemukan.');
            }
            if ($p['status_peminjaman'] !== 'diajukan') {
                throw new BusinessException('Hanya pengajuan berstatus "diajukan" yang dapat disetujui.');
            }
            if (strtotime($p['batas_kembali']) <= time()) {
                throw new BusinessException('Batas kembali sudah lewat. Tolak atau batalkan pengajuan ini.');
            }
            $ids = array_column($this->all('SELECT id_alat FROM detail_peminjaman WHERE id_peminjaman = ?', [$id]), 'id_alat');
            $this->lockAlat($ids);
            $this->exec("UPDATE peminjaman SET status_peminjaman = 'dipinjam', id_petugas = ? WHERE id_peminjaman = ?", [$idPetugas, $id]);
            $this->exec("UPDATE alat a JOIN detail_peminjaman d ON d.id_alat = a.id_alat SET a.status_alat = 'dipinjam' WHERE d.id_peminjaman = ?", [$id]);
        });
    }

    public function reject(int $id, int $idPetugas, string $alasan): void
    {
        $catatan = 'Ditolak' . ($alasan !== '' ? ': ' . $alasan : ' oleh petugas');
        $n = $this->exec("UPDATE peminjaman SET status_peminjaman = 'ditolak', id_petugas = ?, catatan = CONCAT_WS(' | ', NULLIF(catatan, ''), ?) WHERE id_peminjaman = ? AND status_peminjaman = 'diajukan'", [$idPetugas, $catatan, $id]);
        if ($n === 0) {
            throw new BusinessException('Hanya pengajuan berstatus "diajukan" yang dapat ditolak.');
        }
    }

    public function cancelOwn(int $id, int $owner): void
    {
        $n = $this->exec("UPDATE peminjaman SET status_peminjaman = 'dibatalkan' WHERE id_peminjaman = ? AND id_peminjam = ? AND status_peminjaman = 'diajukan'", [$id, $owner]);
        if ($n === 0) {
            throw new BusinessException('Pengajuan tidak dapat dibatalkan.');
        }
    }

    /** Pembatalan oleh admin: diajukan atau dipinjam (alat dilepas kembali). */
    public function batalkan(int $id): void
    {
        $this->tx(function () use ($id) {
            $p = $this->one('SELECT status_peminjaman FROM peminjaman WHERE id_peminjaman = ? FOR UPDATE', [$id]);
            if (!$p || !in_array($p['status_peminjaman'], ['diajukan', 'dipinjam'], true)) {
                throw new BusinessException('Hanya transaksi berstatus diajukan atau dipinjam yang dapat dibatalkan.');
            }
            $this->exec("UPDATE peminjaman SET status_peminjaman = 'dibatalkan' WHERE id_peminjaman = ?", [$id]);
            if ($p['status_peminjaman'] === 'dipinjam') {
                $this->exec("UPDATE alat a JOIN detail_peminjaman d ON d.id_alat = a.id_alat SET a.status_alat = 'tersedia' WHERE d.id_peminjaman = ? AND a.status_alat = 'dipinjam'", [$id]);
            }
        });
    }

    public function updateCatatan(int $id, ?string $catatan): void
    {
        $this->exec('UPDATE peminjaman SET catatan = ? WHERE id_peminjaman = ?', [$catatan, $id]);
    }

    public function delete(int $id): void
    {
        $n = $this->exec("DELETE FROM peminjaman WHERE id_peminjaman = ? AND status_peminjaman IN ('diajukan','ditolak','dibatalkan')", [$id]);
        if ($n === 0) {
            throw new BusinessException('Hanya transaksi berstatus diajukan, ditolak, atau dibatalkan yang dapat dihapus. Transaksi lain disimpan sebagai histori.');
        }
    }
}
